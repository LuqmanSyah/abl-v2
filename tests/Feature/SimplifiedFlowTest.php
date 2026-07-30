<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\DevelopmentPlan;
use App\Models\DevelopmentRequest;
use App\Models\DutyTrip;
use App\Models\EmployeeKpi;
use App\Models\MeritResult;
use App\Models\ReviewPeriod;
use App\Models\User;
use App\Services\AttendanceRecorder;
use App\Services\MeritCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SimplifiedFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_one_trip_accepts_one_server_validated_attendance_per_day(): void
    {
        Storage::fake('local');
        [$hr, $manager, $employee] = $this->users();
        $trip = $this->trip($manager, $employee, now()->subHour(), now()->addDays(2));
        Storage::disk('local')->put('attendance/evidence.jpg', 'photo');

        $recorder = app(AttendanceRecorder::class);
        $attendance = $recorder->record($trip, $employee, [
            'latitude' => -6.2000,
            'longitude' => 106.8166,
            'accuracy_meters' => 20,
        ], 'attendance/evidence.jpg');
        $duplicate = $recorder->record($trip, $employee, [
            'latitude' => -7,
            'longitude' => 107,
            'accuracy_meters' => 999,
        ], 'attendance/duplicate.jpg');

        $this->assertSame($attendance->id, $duplicate->id);
        $this->assertSame(AttendanceStatus::Valid, $attendance->status);
        $this->assertSame(today()->toDateString(), $attendance->attendance_date->toDateString());
        $this->assertDatabaseCount('attendances', 1);

        $this->travelTo(now()->addDay());
        $nextDay = $recorder->record($trip, $employee, [
            'latitude' => -6.2000,
            'longitude' => 106.8166,
            'accuracy_meters' => 20,
        ], 'attendance/next-day.jpg');
        $this->assertNotSame($attendance->id, $nextDay->id);
        $this->assertDatabaseCount('attendances', 2);
        $this->travelBack();

        $this->actingAs($employee)->get(route('attendance.photo', $attendance))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('attendance.photo', $attendance))->assertForbidden();
        $this->actingAs($hr)->get(route('attendance.photo', $attendance))->assertOk();

        $review = $recorder->record($this->trip($manager, $employee), $employee, [
            'latitude' => -7,
            'longitude' => 107,
            'accuracy_meters' => 200,
        ], 'attendance/review.jpg');
        $this->assertSame(AttendanceStatus::NeedsReview, $review->status);

        $review->verifyByHr($hr);
        $this->assertSame(AttendanceStatus::Valid, $review->status);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'attendance.verified',
            'subject_id' => $review->id,
        ]);
    }

    public function test_merit_uses_fixed_80_20_formula_and_locks_published_period(): void
    {
        [$hr, $manager, $employee] = $this->users();
        $period = ReviewPeriod::create([
            'name' => 'Juli',
            'starts_at' => today()->subMonth(),
            'ends_at' => today(),
        ]);
        $kpi = EmployeeKpi::create([
            'review_period_id' => $period->id,
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'name' => 'Target utama',
            'target' => 100,
            'achievement' => 120,
        ]);
        $validTrip = $this->trip(
            $manager,
            $employee,
            now()->subDays(2)->startOfDay()->addHour(),
            now()->subDay()->endOfDay()->subHour(),
        );
        Attendance::create([
            'duty_trip_id' => $validTrip->id,
            'employee_id' => $employee->id,
            'attendance_date' => today()->subDay(),
            'received_at' => now()->subDay(),
            'latitude' => -6.2,
            'longitude' => 106.8166,
            'accuracy_meters' => 10,
            'distance_meters' => 0,
            'photo_path' => 'attendance/evidence.jpg',
            'status' => AttendanceStatus::Valid,
        ]);

        $this->assertSame(1, app(MeritCalculator::class)->publish($period, $hr));
        $result = MeritResult::firstOrFail();
        $this->assertEquals(120, $result->kpi_score);
        $this->assertEquals(50, $result->attendance_score);
        $this->assertEquals(106, $result->total_score);
        $this->assertNotNull($period->fresh()->published_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'merit.published']);

        $this->expectException(BusinessRuleException::class);
        $kpi->update(['achievement' => 100]);
    }

    public function test_development_has_one_plan_and_one_manager_approval_flow(): void
    {
        [$hr, $manager, $employee] = $this->users();
        $otherManager = User::factory()->create(['role' => UserRole::Manager]);
        $plan = DevelopmentPlan::create([
            'employee_id' => $employee->id,
            'target' => 'Kepala tim',
            'current_gap' => 'Delegasi',
            'recommended_action' => 'Mentoring',
            'review_date' => today()->addMonth(),
        ]);

        $this->actingAs($employee);
        $request = DevelopmentRequest::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'type' => DevelopmentRequest::TYPE_MENTORING,
            'title' => 'Mentoring delegasi',
            'reason' => 'Persiapan promosi',
        ]);

        try {
            $request->approve($otherManager);
            $this->fail('Atasan lain tidak boleh menyetujui pengajuan.');
        } catch (BusinessRuleException) {
            $this->assertSame(DevelopmentRequest::STATUS_PENDING, $request->fresh()->status);
        }

        $request->approve($manager, 'Disetujui');
        $request->complete($hr, 'Selesai');

        $this->assertSame(DevelopmentRequest::STATUS_COMPLETED, $request->status);
        $this->assertTrue(DevelopmentPlan::visibleTo($employee)->whereKey($plan->id)->exists());
        $this->assertTrue(DevelopmentPlan::visibleTo($manager)->whereKey($plan->id)->exists());
        $this->assertTrue(DevelopmentRequest::visibleTo($employee)->whereKey($request->id)->exists());
        $this->assertTrue(DevelopmentRequest::visibleTo($manager)->whereKey($request->id)->exists());
        $this->assertSame(2, ActivityLog::whereIn('action', [
            'development.approved',
            'development.completed',
        ])->count());
    }

    /** @return array{User, User, User} */
    private function users(): array
    {
        $hr = User::factory()->create(['role' => UserRole::Hr]);
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'manager_id' => $manager->id,
        ]);

        return [$hr, $manager, $employee];
    }

    private function trip(User $manager, User $employee, $startsAt = null, $endsAt = null): DutyTrip
    {
        return DutyTrip::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'location_name' => 'Kantor',
            'address' => 'Jakarta',
            'latitude' => -6.2000,
            'longitude' => 106.8166,
            'radius_meters' => 100,
            'starts_at' => $startsAt ?? now()->subHour(),
            'ends_at' => $endsAt ?? now()->addHour(),
            'status' => DutyTripStatus::Active,
        ]);
    }
}
