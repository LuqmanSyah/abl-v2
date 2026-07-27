<?php

namespace Tests\Feature;

use App\Enums\AttendanceRequestStatus;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\FlowType;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Filament\Resources\AttendanceResource\Pages\ListAttendances;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BranchOffice;
use App\Models\DailyAttendanceSummary;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceService;
use App\Services\GoogleMapsService;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_office_check_in_within_radius_is_recorded_once_per_day(): void
    {
        $user = $this->employee();
        $service = app(AttendanceService::class);

        $attendance = $service->record(
            $user,
            AttendanceType::CheckIn,
            -6.2088,
            106.8456,
            'attendance/in.jpg',
            recordedAt: now()->setDate(2026, 8, 1)->setTime(8, 0),
        );

        $this->assertSame(AttendanceStatus::Normal, $attendance->status);
        $this->assertSame('office', $attendance->session_key);

        $this->expectException(BusinessRuleException::class);
        $service->record(
            $user,
            AttendanceType::CheckIn,
            -6.2088,
            106.8456,
            'attendance/in-2.jpg',
            recordedAt: now()->setDate(2026, 8, 1)->setTime(8, 5),
        );
    }

    public function test_office_check_in_outside_radius_is_rejected(): void
    {
        $service = app(AttendanceService::class);

        $this->expectException(BusinessRuleException::class);
        $service->record(
            $this->employee(),
            AttendanceType::CheckIn,
            -6.2,
            106.8,
            'attendance/in.jpg',
            recordedAt: now()->setDate(2026, 8, 1)->setTime(8, 0),
        );
    }

    public function test_only_approved_attendance_request_can_be_used(): void
    {
        $user = $this->employee();
        $approved = $this->attendanceRequest($user, AttendanceRequestStatus::Approved, '2026-08-01');
        $service = app(AttendanceService::class);

        $service->record(
            $user,
            AttendanceType::CheckIn,
            -6.2088,
            106.8456,
            'attendance/office.jpg',
            recordedAt: now()->setDate(2026, 8, 1)->setTime(8, 0),
        );

        $attendance = $service->record(
            $user,
            AttendanceType::CheckIn,
            -6.1754,
            106.8272,
            'attendance/duty.jpg',
            $approved,
            recordedAt: now()->setDate(2026, 8, 1)->setTime(8, 0),
        );

        $this->assertTrue($attendance->attendanceRequest->is($approved));
        $this->assertDatabaseCount('attendances', 2);

        $pending = $this->attendanceRequest($user, AttendanceRequestStatus::Pending, '2026-08-02');
        $this->expectException(BusinessRuleException::class);
        $service->record(
            $user,
            AttendanceType::CheckIn,
            -6.1754,
            106.8272,
            'attendance/pending.jpg',
            $pending,
            recordedAt: now()->setDate(2026, 8, 2)->setTime(8, 0),
        );
    }

    public function test_top_down_attendance_request_is_auto_approved(): void
    {
        $user = $this->employee();
        $manager = User::factory()->create([
            'position_id' => $user->position_id,
            'work_schedule_id' => $user->work_schedule_id,
            'branch_office_id' => $user->branch_office_id,
            'role' => UserRole::Manager,
        ]);
        $user->update(['manager_id' => $manager->id]);

        $request = $this->attendanceRequest(
            $user,
            AttendanceRequestStatus::Pending,
            '2026-08-01',
            FlowType::TopDown,
            $manager,
        );

        $this->assertSame(AttendanceRequestStatus::Approved, $request->status);
        $this->assertSame($manager->id, $request->approved_by);
    }

    public function test_approved_leave_blocks_same_day_attendance_request(): void
    {
        $user = $this->employee();
        $hr = User::factory()->create([
            'position_id' => $user->position_id,
            'work_schedule_id' => $user->work_schedule_id,
            'branch_office_id' => $user->branch_office_id,
            'role' => UserRole::HrAdmin,
        ]);
        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'type' => LeaveType::PaidLeave,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-01',
            'reason' => 'Cuti',
            'status' => LeaveStatus::Pending,
        ]);
        $leave->approve($hr);

        $this->expectExceptionMessage('Tugas luar tumpang tindih dengan cuti yang sudah disetujui.');

        $this->attendanceRequest($user, AttendanceRequestStatus::Pending, '2026-08-01');
    }

    public function test_approved_attendance_request_blocks_same_day_leave(): void
    {
        $user = $this->employee();
        $this->attendanceRequest($user, AttendanceRequestStatus::Approved, '2026-08-01');

        $this->expectExceptionMessage('Cuti tumpang tindih dengan tugas luar yang sudah disetujui.');

        LeaveRequest::create([
            'user_id' => $user->id,
            'type' => LeaveType::PaidLeave,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-01',
            'reason' => 'Cuti',
            'status' => LeaveStatus::Pending,
        ]);
    }

    public function test_leave_approval_creates_summaries_for_inclusive_date_range(): void
    {
        $user = $this->employee();
        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'type' => LeaveType::PaidLeave,
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-03',
            'reason' => 'Cuti',
            'status' => LeaveStatus::Pending,
        ]);
        $hr = User::factory()->create([
            'position_id' => $user->position_id,
            'work_schedule_id' => $user->work_schedule_id,
            'branch_office_id' => $user->branch_office_id,
            'role' => UserRole::HrAdmin,
        ]);

        $leave->approve($hr);

        $this->assertSame(
            ['2026-08-01', '2026-08-02', '2026-08-03'],
            DailyAttendanceSummary::query()
                ->where('user_id', $user->id)
                ->orderBy('date')
                ->pluck('date')
                ->map->toDateString()
                ->all(),
        );
    }

    public function test_early_check_in_is_rejected_and_past_cutoff_is_alfa(): void
    {
        $user = $this->employee();
        $service = app(AttendanceService::class);

        try {
            $service->record(
                $user,
                AttendanceType::CheckIn,
                -6.2088,
                106.8456,
                'attendance/early.jpg',
                recordedAt: now()->setDate(2026, 8, 1)->setTime(6, 29),
            );
            $this->fail('Early check-in should be rejected.');
        } catch (BusinessRuleException) {
            $this->assertDatabaseCount('attendances', 0);
        }

        $attendance = $service->record(
            $user,
            AttendanceType::CheckIn,
            -6.2088,
            106.8456,
            'attendance/alfa.jpg',
            recordedAt: now()->setDate(2026, 8, 1)->setTime(10, 1),
        );

        $this->assertSame(AttendanceStatus::Alfa, $attendance->status);
    }

    public function test_geofencing_uses_haversine_without_google_distance_api(): void
    {
        $maps = Mockery::mock(GoogleMapsService::class);
        $maps->shouldNotReceive('distance');

        $attendance = (new AttendanceService($maps))->record(
            $this->employee(),
            AttendanceType::CheckIn,
            -6.2088,
            106.8456,
            'attendance/fallback.jpg',
            recordedAt: now()->setDate(2026, 8, 1)->setTime(8, 0),
        );

        $this->assertFalse($attendance->is_fallback);
        $this->assertEquals(0, $attendance->distance_to_target_meters);
    }

    public function test_outside_radius_check_out_requires_exception_and_is_pending(): void
    {
        $user = $this->employee();
        $service = app(AttendanceService::class);
        $manager = $user->manager;
        $service->record(
            $user,
            AttendanceType::CheckIn,
            -6.2088,
            106.8456,
            'attendance/in.jpg',
            recordedAt: CarbonImmutable::parse('2026-08-01 08:00:00', 'Asia/Jakarta'),
        );

        try {
            $service->record(
                $user,
                AttendanceType::CheckOut,
                -6.2,
                106.8,
                'attendance/no-reason.jpg',
                recordedAt: now()->setDate(2026, 8, 1)->setTime(17, 0),
            );
            $this->fail('Outside-radius check-out without a reason should be rejected.');
        } catch (BusinessRuleException) {
            $this->assertDatabaseCount('attendances', 1);
        }

        $attendance = $service->record(
            $user,
            AttendanceType::CheckOut,
            -6.2,
            106.8,
            'attendance/out.jpg',
            exceptionReason: 'Pertemuan berakhir di lokasi klien.',
            recordedAt: now()->setDate(2026, 8, 1)->setTime(17, 0),
        );

        $this->assertSame(AttendanceStatus::PendingVerification, $attendance->status);
        $this->assertTrue($attendance->is_radius_exception);

        $this->travelTo(CarbonImmutable::parse('2026-08-01 18:00:00', 'Asia/Jakarta'));
        $this->actingAs($manager);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ListAttendances::class)
            ->callAction(TestAction::make('approve_exception')->table($attendance));

        $this->assertSame(AttendanceStatus::Normal, $attendance->fresh()->status);
    }

    public function test_last_day_check_out_at_home_branch_is_allowed(): void
    {
        $user = $this->employee();
        $request = $this->attendanceRequest($user, AttendanceRequestStatus::Approved, '2026-08-01');
        app(AttendanceService::class)->record(
            $user,
            AttendanceType::CheckIn,
            -6.1754,
            106.8272,
            'attendance/duty-in.jpg',
            $request,
            recordedAt: CarbonImmutable::parse('2026-08-01 08:00:00', 'Asia/Jakarta'),
        );

        $attendance = app(AttendanceService::class)->record(
            $user,
            AttendanceType::CheckOut,
            -6.2088,
            106.8456,
            'attendance/home.jpg',
            $request,
            recordedAt: now()->setDate(2026, 8, 1)->setTime(17, 1),
        );

        $this->assertSame(AttendanceStatus::Normal, $attendance->status);
        $this->assertSame('Kantor Pusat', $attendance->address_snapshot);
    }

    public function test_check_out_requires_an_earlier_open_check_in_in_the_same_session(): void
    {
        $user = $this->employee();
        $service = app(AttendanceService::class);
        $checkOutAt = CarbonImmutable::parse('2026-08-01 17:00:00', 'Asia/Jakarta');

        try {
            $service->record(
                $user,
                AttendanceType::CheckOut,
                -6.2088,
                106.8456,
                'attendance/no-in.jpg',
                recordedAt: $checkOutAt,
            );
            $this->fail('Check-out without check-in should fail.');
        } catch (BusinessRuleException) {
            $this->assertDatabaseCount('attendances', 0);
        }

        $futureCheckIn = Attendance::create([
            'user_id' => $user->id,
            'type' => AttendanceType::CheckIn,
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'distance_to_target_meters' => 0,
            'address_snapshot' => 'Kantor Pusat',
            'photo_path' => 'attendance/future-in.jpg',
            'status' => AttendanceStatus::Normal,
            'recorded_at' => $checkOutAt->addHour(),
        ]);

        try {
            $service->record(
                $user,
                AttendanceType::CheckOut,
                -6.2088,
                106.8456,
                'attendance/before-in.jpg',
                recordedAt: $checkOutAt,
            );
            $this->fail('Check-out before check-in should fail.');
        } catch (BusinessRuleException) {
            $this->assertDatabaseCount('attendances', 1);
        }
        $futureCheckIn->delete();

        $service->record(
            $user,
            AttendanceType::CheckIn,
            -6.2088,
            106.8456,
            'attendance/in.jpg',
            recordedAt: $checkOutAt->subHour(),
        );
        $service->record(
            $user,
            AttendanceType::CheckOut,
            -6.2088,
            106.8456,
            'attendance/out.jpg',
            recordedAt: $checkOutAt,
        );

        $this->expectExceptionMessage('Check-out untuk sesi ini sudah tercatat hari ini.');
        $service->record(
            $user,
            AttendanceType::CheckOut,
            -6.2088,
            106.8456,
            'attendance/out-2.jpg',
            recordedAt: $checkOutAt->addMinute(),
        );
    }

    public function test_attendance_form_renders_gps_capture_and_photo_fields(): void
    {
        $user = $this->employee();
        $user->update([
            'role' => UserRole::Manager,
            'manager_id' => null,
        ]);

        $this->actingAs($user)
            ->get(route('filament.employee.resources.attendances.create'))
            ->assertOk()
            ->assertSee('Ambil Lokasi GPS')
            ->assertSee('Live Selfie')
            ->assertSee('capture="user"', false);

        $this->get(route('filament.admin.resources.attendances.create'))
            ->assertForbidden();
    }

    private function employee(): User
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $position = Position::create([
            'department_id' => $department->id,
            'title' => 'Engineer',
            'level' => 1,
        ]);
        $schedule = WorkSchedule::create([
            'name' => 'Regular',
            'check_in_time' => '08:00:00',
            'check_out_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
            'alfa_cutoff_minutes' => 120,
        ]);
        $branch = BranchOffice::create([
            'name' => 'Kantor Pusat',
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
        ]);
    }

    private function attendanceRequest(
        User $user,
        AttendanceRequestStatus $status,
        string $date,
        FlowType $flowType = FlowType::BottomUp,
        ?User $creator = null,
    ): AttendanceRequest {
        if ($status === AttendanceRequestStatus::Approved && ! $user->manager_id) {
            $manager = User::factory()->create([
                'position_id' => $user->position_id,
                'work_schedule_id' => $user->work_schedule_id,
                'branch_office_id' => $user->branch_office_id,
                'role' => UserRole::Manager,
            ]);
            $user->update(['manager_id' => $manager->id]);
        }

        $request = AttendanceRequest::create([
            'user_id' => $user->id,
            'created_by' => $creator?->id ?? $user->id,
            'flow_type' => $flowType,
            'destination_name' => 'Client Office',
            'destination_address' => 'Jakarta',
            'target_latitude' => -6.1754,
            'target_longitude' => 106.8272,
            'allowed_radius_meters' => 100,
            'duty_start_datetime' => "{$date} 08:00:00",
            'duty_end_datetime' => "{$date} 17:00:00",
            'reason' => 'Meeting',
            'status' => $status,
            'approved_by' => $status === AttendanceRequestStatus::Approved ? $user->manager_id : null,
        ]);

        if ($status === AttendanceRequestStatus::Approved && $flowType === FlowType::BottomUp) {
            $request->approve($user->manager);
        }

        return $request;
    }
}
