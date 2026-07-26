<?php

namespace Tests\Feature;

use App\Enums\DailySummaryStatus;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\BranchOffice;
use App\Models\DailyAttendanceSummary;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private AttendanceScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $position = Position::create(['department_id' => $department->id, 'title' => 'Engineer', 'level' => 1]);
        $schedule = WorkSchedule::create([
            'name' => 'Regular',
            'check_in_time' => '08:00',
            'check_out_time' => '17:00',
            'late_tolerance_minutes' => 15,
            'alfa_cutoff_minutes' => 120,
        ]);
        $branch = BranchOffice::create([
            'name' => 'Jakarta',
            'code' => 'JKT',
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'allowed_radius_meters' => 100,
        ]);

        $this->user = User::factory()->create([
            'position_id' => $position->id,
            'work_schedule_id' => $schedule->id,
            'branch_office_id' => $branch->id,
            'join_date' => '2026-01-01',
        ]);
        $this->service = new AttendanceScoreService;
    }

    public function test_full_attendance_scores_100(): void
    {
        foreach (['03', '04', '05', '06', '07'] as $day) {
            $this->summary("2026-08-{$day}", DailySummaryStatus::Present);
        }

        $this->assertSame(100, $this->service->calculate($this->user->id, '2026-08-03', '2026-08-07'));
    }

    public function test_three_late_days_score_94(): void
    {
        foreach (['03', '04', '05'] as $day) {
            $this->summary("2026-08-{$day}", DailySummaryStatus::Late);
        }

        foreach (['06', '07'] as $day) {
            $this->summary("2026-08-{$day}", DailySummaryStatus::Present);
        }

        $this->assertSame(94, $this->service->calculate($this->user->id, '2026-08-03', '2026-08-07'));
    }

    public function test_two_alfa_days_score_80(): void
    {
        foreach (['03', '04', '05'] as $day) {
            $this->summary("2026-08-{$day}", DailySummaryStatus::Present);
        }

        foreach (['06', '07'] as $day) {
            $this->summary("2026-08-{$day}", DailySummaryStatus::Alfa);
        }

        $this->assertSame(80, $this->service->calculate($this->user->id, '2026-08-03', '2026-08-07'));
    }

    public function test_missing_checkout_applies_missing_and_alfa_penalties(): void
    {
        foreach (['03', '04', '05', '06'] as $day) {
            $this->summary("2026-08-{$day}", DailySummaryStatus::Present);
        }

        $this->summary('2026-08-07', DailySummaryStatus::MissingCheckout);

        $this->assertSame(85, $this->service->calculate($this->user->id, '2026-08-03', '2026-08-07'));
    }

    public function test_join_date_adjusts_effective_start(): void
    {
        $this->user->update(['join_date' => '2026-08-06']);
        $this->summary('2026-08-06', DailySummaryStatus::Present);
        $this->summary('2026-08-07', DailySummaryStatus::Present);

        $this->assertSame(100, $this->service->calculate($this->user->id, '2026-08-03', '2026-08-07'));
    }

    public function test_holidays_and_partial_approved_leave_are_excluded(): void
    {
        Holiday::create(['name' => 'Libur Nasional', 'date' => '2026-08-03']);
        LeaveRequest::create([
            'user_id' => $this->user->id,
            'type' => LeaveType::PaidLeave,
            'start_date' => '2026-08-02',
            'end_date' => '2026-08-05',
            'reason' => 'Keluarga',
            'status' => LeaveStatus::Approved,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);
        $this->summary('2026-08-06', DailySummaryStatus::Present);
        $this->summary('2026-08-07', DailySummaryStatus::Present);

        $this->assertSame(100, $this->service->calculate($this->user->id, '2026-08-03', '2026-08-07'));
    }

    private function summary(string $date, DailySummaryStatus $status): void
    {
        DailyAttendanceSummary::updateOrCreate(
            ['user_id' => $this->user->id, 'date' => $date],
            ['status' => $status, 'late_minutes' => 0],
        );
    }
}
