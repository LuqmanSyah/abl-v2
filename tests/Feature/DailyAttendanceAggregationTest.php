<?php

namespace Tests\Feature;

use App\Console\Commands\AggregateDailyAttendance;
use App\Enums\AttendanceRequestStatus;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\DailySummaryStatus;
use App\Enums\FlowType;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BranchOffice;
use App\Models\DailyAttendanceSummary;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyAttendanceAggregationTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_summary_wins_over_attendance(): void
    {
        $user = $this->employee();
        $checkIn = $this->attendance($user, AttendanceType::CheckIn, '2026-08-03 08:00:00');
        $this->attendance($user, AttendanceType::CheckOut, '2026-08-03 17:00:00');
        DailyAttendanceSummary::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '2026-08-03')
            ->firstOrFail()
            ->update([
                'attendance_request_id' => null,
                'check_in_id' => null,
                'check_out_id' => null,
                'status' => DailySummaryStatus::Leave,
                'late_minutes' => 0,
            ]);

        $this->artisan('attendance:aggregate', ['--date' => '2026-08-03'])->assertSuccessful();

        $summary = DailyAttendanceSummary::query()->where('user_id', $user->id)->sole();
        $this->assertSame(DailySummaryStatus::Leave, $summary->status);
        $this->assertNull($summary->check_in_id);
        $this->assertNotSame($checkIn->id, $summary->check_in_id);
    }

    public function test_earliest_valid_duty_session_overrides_office_session(): void
    {
        $user = $this->employee();
        $request = $this->attendanceRequest($user);
        $this->attendance($user, AttendanceType::CheckIn, '2026-08-03 08:00:00');
        $this->attendance($user, AttendanceType::CheckOut, '2026-08-03 17:00:00');
        $dutyCheckIn = $this->attendance(
            $user,
            AttendanceType::CheckIn,
            '2026-08-03 08:20:00',
            AttendanceStatus::Late,
            $request,
        );
        $dutyCheckOut = $this->attendance(
            $user,
            AttendanceType::CheckOut,
            '2026-08-03 17:00:00',
            request: $request,
        );

        $this->artisan('attendance:aggregate', ['--date' => '2026-08-03'])->assertSuccessful();
        $this->artisan('attendance:aggregate', ['--date' => '2026-08-03'])->assertSuccessful();

        $summary = DailyAttendanceSummary::query()->where('user_id', $user->id)->sole();
        $this->assertSame($request->id, $summary->attendance_request_id);
        $this->assertSame($dutyCheckIn->id, $summary->check_in_id);
        $this->assertSame($dutyCheckOut->id, $summary->check_out_id);
        $this->assertSame(DailySummaryStatus::Late, $summary->status);
        $this->assertSame(20, $summary->late_minutes);
    }

    public function test_pending_verification_check_out_becomes_missing_checkout(): void
    {
        $user = $this->employee();
        $checkIn = $this->attendance($user, AttendanceType::CheckIn, '2026-08-03 08:00:00');
        $checkOut = $this->attendance(
            $user,
            AttendanceType::CheckOut,
            '2026-08-03 17:00:00',
            AttendanceStatus::PendingVerification,
        );

        $this->artisan('attendance:aggregate', ['--date' => '2026-08-03'])->assertSuccessful();

        $summary = DailyAttendanceSummary::query()->where('user_id', $user->id)->sole();
        $this->assertSame(DailySummaryStatus::MissingCheckout, $summary->status);
        $this->assertSame($checkIn->id, $summary->check_in_id);
        $this->assertSame($checkOut->id, $summary->check_out_id);
    }

    public function test_holiday_create_and_update_populates_active_users(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-08-05 12:00:00', 'Asia/Jakarta'));
        $active = $this->employee();
        $inactive = $this->employee(false);
        $holiday = Holiday::create(['name' => 'Libur', 'date' => '2026-08-03']);

        $this->assertDatabaseHas('daily_attendance_summaries', [
            'user_id' => $active->id,
            'date' => '2026-08-03 00:00:00',
            'status' => DailySummaryStatus::Holiday->value,
        ]);
        $this->assertDatabaseMissing('daily_attendance_summaries', [
            'user_id' => $inactive->id,
            'date' => '2026-08-03 00:00:00',
        ]);

        DailyAttendanceSummary::query()->where('user_id', $active->id)->delete();
        $this->artisan('attendance:populate-holidays', ['--date' => '2026-08-03'])->assertSuccessful();
        $this->assertDatabaseHas('daily_attendance_summaries', [
            'user_id' => $active->id,
            'date' => '2026-08-03 00:00:00',
            'status' => DailySummaryStatus::Holiday->value,
        ]);

        $holiday->update(['date' => '2026-08-04']);

        $this->assertDatabaseHas('daily_attendance_summaries', [
            'user_id' => $active->id,
            'date' => '2026-08-03 00:00:00',
            'status' => DailySummaryStatus::Alfa->value,
        ]);
        $this->assertDatabaseHas('daily_attendance_summaries', [
            'user_id' => $active->id,
            'date' => '2026-08-04 00:00:00',
            'status' => DailySummaryStatus::Holiday->value,
        ]);
        $this->travelBack();
    }

    public function test_holiday_deletion_does_not_finalize_pending_exception_today(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-08-03 12:00:00', 'Asia/Jakarta'));
        $user = $this->employee();
        $holiday = Holiday::create(['name' => 'Libur', 'date' => '2026-08-03']);
        $this->attendance($user, AttendanceType::CheckIn, '2026-08-03 08:00:00');
        $pending = $this->attendance(
            $user,
            AttendanceType::CheckOut,
            '2026-08-03 11:00:00',
            AttendanceStatus::PendingVerification,
        );

        $holiday->delete();

        $this->assertSame(AttendanceStatus::PendingVerification, $pending->fresh()->status);
        $this->assertDatabaseMissing('daily_attendance_summaries', [
            'user_id' => $user->id,
            'date' => '2026-08-03 00:00:00',
        ]);
        $this->travelBack();
    }

    public function test_aggregation_is_scheduled_for_2359_jakarta_time(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event): bool => str_contains($event->command, 'attendance:aggregate'));

        $this->assertNotNull($event);
        $this->assertSame('59 23 * * *', $event->expression);
        $this->assertSame('Asia/Jakarta', $event->timezone);
    }

    public function test_weekend_does_not_create_alfa_summary(): void
    {
        $user = $this->employee();

        $this->artisan('attendance:aggregate', ['--date' => '2026-08-01'])->assertSuccessful();

        $this->assertDatabaseMissing('daily_attendance_summaries', [
            'user_id' => $user->id,
            'date' => '2026-08-01 00:00:00',
        ]);
    }

    public function test_pending_exception_is_only_finalized_at_cutoff(): void
    {
        $user = $this->employee();
        $manager = User::factory()->create([
            'position_id' => $user->position_id,
            'work_schedule_id' => $user->work_schedule_id,
            'branch_office_id' => $user->branch_office_id,
            'role' => UserRole::Manager,
        ]);
        $user->update(['manager_id' => $manager->id]);
        $this->attendance($user, AttendanceType::CheckIn, '2026-08-03 08:00:00');
        $pending = $this->attendance(
            $user,
            AttendanceType::CheckOut,
            '2026-08-03 17:00:00',
            AttendanceStatus::PendingVerification,
        );

        app(AggregateDailyAttendance::class)->aggregate('2026-08-03', finalize: false);

        $this->assertDatabaseMissing('daily_attendance_summaries', ['user_id' => $user->id]);
        $this->assertSame(AttendanceStatus::PendingVerification, $pending->fresh()->status);

        $this->artisan('attendance:aggregate', ['--date' => '2026-08-03'])->assertSuccessful();

        $this->assertSame(AttendanceStatus::Rejected, $pending->fresh()->status);
        $this->assertSame(
            DailySummaryStatus::MissingCheckout,
            DailyAttendanceSummary::query()->where('user_id', $user->id)->sole()->status,
        );

        $this->expectException(BusinessRuleException::class);
        $pending->approveException($manager);
    }

    private function employee(bool $active = true): User
    {
        $department = Department::create([
            'name' => fake()->unique()->word(),
            'code' => fake()->unique()->lexify('???'),
        ]);
        $position = Position::create([
            'department_id' => $department->id,
            'title' => 'Engineer',
            'level' => 1,
        ]);
        $schedule = WorkSchedule::create([
            'name' => fake()->unique()->word(),
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
            'alfa_cutoff_minutes' => 120,
        ]);
        $branch = BranchOffice::create([
            'name' => fake()->unique()->word(),
            'code' => fake()->unique()->lexify('???'),
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'allowed_radius_meters' => 100,
        ]);

        $manager = User::factory()->create([
            'position_id' => $position->id,
            'work_schedule_id' => $schedule->id,
            'branch_office_id' => $branch->id,
            'role' => UserRole::Manager,
        ]);

        return User::factory()->create([
            'position_id' => $position->id,
            'work_schedule_id' => $schedule->id,
            'branch_office_id' => $branch->id,
            'manager_id' => $manager->id,
            'status' => $active,
        ]);
    }

    private function attendanceRequest(User $user): AttendanceRequest
    {
        $manager = $user->manager;

        $request = AttendanceRequest::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'flow_type' => FlowType::BottomUp,
            'destination_name' => 'Client Office',
            'destination_address' => 'Jakarta',
            'target_latitude' => -6.1754,
            'target_longitude' => 106.8272,
            'allowed_radius_meters' => 100,
            'duty_start_datetime' => '2026-08-03 08:00:00',
            'duty_end_datetime' => '2026-08-03 17:00:00',
            'reason' => 'Meeting',
            'status' => AttendanceRequestStatus::Pending,
        ]);

        $request->approve($manager);

        return $request;
    }

    private function attendance(
        User $user,
        AttendanceType $type,
        string $recordedAt,
        AttendanceStatus $status = AttendanceStatus::Normal,
        ?AttendanceRequest $request = null,
    ): Attendance {
        return Attendance::create([
            'user_id' => $user->id,
            'attendance_request_id' => $request?->id,
            'type' => $type,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'distance_to_target_meters' => 10,
            'address_snapshot' => 'Jakarta',
            'photo_path' => 'attendance/test.jpg',
            'is_radius_exception' => $status === AttendanceStatus::PendingVerification,
            'exception_reason' => $status === AttendanceStatus::PendingVerification
                ? 'Selesai di luar lokasi.'
                : null,
            'status' => $status,
            'recorded_at' => $recordedAt,
        ]);
    }
}
