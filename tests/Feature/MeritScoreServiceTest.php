<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\DailySummaryStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\DailyAttendanceSummary;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\PerformanceReview;
use App\Models\User;
use App\Services\MeritScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeritScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_change_recalculates_known_merit_score(): void
    {
        $review = $this->makeReview('2030-01-07');
        $attendance = [
            'user_id' => $review->user_id,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_to_target_meters' => 10,
            'address_snapshot' => 'Kantor',
            'photo_path' => 'attendance/test.jpg',
            'status' => AttendanceStatus::Normal,
        ];

        Attendance::create($attendance + [
            'type' => AttendanceType::CheckIn,
            'recorded_at' => '2030-01-07 08:00:00',
        ]);
        Attendance::create($attendance + [
            'type' => AttendanceType::CheckOut,
            'recorded_at' => '2030-01-07 17:00:00',
        ]);

        $review->refresh();

        $this->assertDatabaseHas(DailyAttendanceSummary::class, [
            'user_id' => $review->user_id,
            'date' => '2030-01-07 00:00:00',
            'status' => DailySummaryStatus::Present->value,
        ]);
        $this->assertSame(100.0, (float) $review->attendance_score);
        $this->assertSame(80.0, (float) $review->manager_kpi_score);
        $this->assertSame(84.0, (float) $review->final_merit_score);
        $this->assertSame('B', $review->grade);
    }

    public function test_retroactive_leave_approval_recalculates_overlapping_review(): void
    {
        $review = $this->makeReview('2030-01-08');
        app(MeritScoreService::class)->calculate($review);
        $this->assertSame(82.0, (float) $review->fresh()->final_merit_score);

        $leave = LeaveRequest::create([
            'user_id' => $review->user_id,
            'type' => LeaveType::PaidLeave,
            'start_date' => '2030-01-08',
            'end_date' => '2030-01-08',
            'reason' => 'Cuti retroaktif',
            'status' => LeaveStatus::Pending,
        ]);
        $leave->update([
            'status' => LeaveStatus::Approved,
            'approved_by' => $review->reviewer_id,
            'approved_at' => now(),
        ]);

        $review->refresh();

        $this->assertSame(100.0, (float) $review->attendance_score);
        $this->assertSame(84.0, (float) $review->final_merit_score);
        $this->assertSame('B', $review->grade);
    }

    public function test_locked_review_blocks_automatic_but_allows_forced_recalculation(): void
    {
        $review = $this->makeReview('2030-01-09', ReviewStatus::Locked);
        $review->update([
            'attendance_score' => 12,
            'manager_kpi_score' => 34,
            'final_merit_score' => 56,
            'grade' => 'D',
        ]);

        Holiday::create(['name' => 'Libur Baru', 'date' => '2030-01-09']);

        $review->refresh();
        $this->assertSame(12.0, (float) $review->attendance_score);
        $this->assertSame(56.0, (float) $review->final_merit_score);

        try {
            app(MeritScoreService::class)->calculate($review);
            $this->fail('Rapor terkunci seharusnya menolak kalkulasi otomatis.');
        } catch (BusinessRuleException $exception) {
            $this->assertSame('Rapor terkunci hanya dapat dihitung ulang secara paksa.', $exception->getMessage());
        }

        app(MeritScoreService::class)->calculate($review, force: true);

        $review->refresh();
        $this->assertSame(100.0, (float) $review->attendance_score);
        $this->assertSame(84.0, (float) $review->final_merit_score);
        $this->assertSame('B', $review->grade);
    }

    private function makeReview(string $date, ReviewStatus $status = ReviewStatus::Draft): PerformanceReview
    {
        $this->seed();

        $employee = User::query()->where('role', UserRole::Employee)->firstOrFail();
        $employee->update(['join_date' => '2020-01-01']);
        $review = PerformanceReview::create([
            'user_id' => $employee->id,
            'reviewer_id' => $employee->manager_id,
            'period' => $date,
            'start_date' => $date,
            'end_date' => $date,
            'status' => $status,
        ]);
        $review->reviewKpiDetails->each->update(['manager_score' => 80]);

        return $review;
    }
}
