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
use App\Filament\Resources\AttendanceRequestResource;
use App\Filament\Resources\AttendanceRequestResource\Pages\CreateAttendanceRequest;
use App\Filament\Resources\LeaveRequestResource\Pages\CreateLeaveRequest;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceWorkflowSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_pages_ignore_forged_workflow_state(): void
    {
        $this->seed();
        $employee = User::query()->where('nip', 'NIP006')->firstOrFail();
        $foreign = User::query()->where('nip', 'NIP008')->firstOrFail();
        $foreignManager = $foreign->manager;

        $this->actingAs($employee);
        Filament::setCurrentPanel(Filament::getPanel('employee'));

        Livewire::test(CreateAttendanceRequest::class)
            ->fillForm([
                'user_id' => $foreign->id,
                'created_by' => $foreignManager->id,
                'flow_type' => FlowType::TopDown->value,
                'destination_name' => 'Klien A',
                'destination_address' => 'Jakarta',
                'target_latitude' => -6.1754,
                'target_longitude' => 106.8272,
                'allowed_radius_meters' => 100,
                'duty_start_datetime' => '2030-08-05 08:00:00',
                'duty_end_datetime' => '2030-08-05 17:00:00',
                'reason' => 'Meeting',
                'status' => AttendanceRequestStatus::Approved->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $request = AttendanceRequest::sole();
        $this->assertSame($employee->id, $request->user_id);
        $this->assertSame($employee->id, $request->created_by);
        $this->assertSame(FlowType::BottomUp, $request->flow_type);
        $this->assertSame(AttendanceRequestStatus::Pending, $request->status);
        $this->assertNull($request->approved_by);

        Livewire::test(CreateLeaveRequest::class)
            ->fillForm([
                'user_id' => $foreign->id,
                'type' => LeaveType::PaidLeave->value,
                'start_date' => '2030-08-06',
                'end_date' => '2030-08-06',
                'reason' => 'Keluarga',
                'status' => LeaveStatus::Approved->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $leave = LeaveRequest::sole();
        $this->assertSame($employee->id, $leave->user_id);
        $this->assertSame(LeaveStatus::Pending, $leave->status);
        $this->assertNull($leave->approved_by);
        $this->assertNull($leave->approved_at);

        $manager = $employee->manager;
        $this->actingAs($manager);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreateAttendanceRequest::class)
            ->fillForm([
                'user_id' => $employee->id,
                'created_by' => $foreignManager->id,
                'flow_type' => FlowType::BottomUp->value,
                'destination_name' => 'Klien B',
                'destination_address' => 'Bandung',
                'target_latitude' => -6.9175,
                'target_longitude' => 107.6191,
                'allowed_radius_meters' => 100,
                'duty_start_datetime' => '2030-08-07 08:00:00',
                'duty_end_datetime' => '2030-08-07 17:00:00',
                'reason' => 'Audit',
                'status' => AttendanceRequestStatus::Rejected->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $assigned = AttendanceRequest::query()->latest('id')->firstOrFail();
        $this->assertSame($manager->id, $assigned->created_by);
        $this->assertSame(FlowType::TopDown, $assigned->flow_type);
        $this->assertSame(AttendanceRequestStatus::Approved, $assigned->status);
        $this->assertSame($manager->id, $assigned->approved_by);
    }

    public function test_only_direct_manager_can_decide_duty_and_exception(): void
    {
        $this->seed();
        Notification::fake();
        $employee = User::query()->where('nip', 'NIP006')->firstOrFail();
        $manager = $employee->manager;
        $foreignManager = User::query()->where('nip', 'NIP005')->firstOrFail();
        $hr = User::query()->where('role', UserRole::HrAdmin)->firstOrFail();
        $request = $this->pendingRequest($employee, '2030-08-08');

        foreach ([$foreignManager, $hr] as $actor) {
            try {
                $request->approve($actor);
                $this->fail('Foreign actor should not approve duty.');
            } catch (BusinessRuleException) {
                $this->assertSame(AttendanceRequestStatus::Pending, $request->fresh()->status);
            }
        }

        $request->approve($manager);
        $this->assertSame(AttendanceRequestStatus::Approved, $request->fresh()->status);

        $this->travelTo(CarbonImmutable::parse('2030-08-08 18:00:00', 'Asia/Jakarta'));
        $attendance = Attendance::create([
            'user_id' => $employee->id,
            'type' => AttendanceType::CheckOut,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_to_target_meters' => 500,
            'address_snapshot' => 'Lokasi klien',
            'photo_path' => 'attendance/live-selfie.jpg',
            'is_radius_exception' => true,
            'exception_reason' => 'Selesai di lokasi klien.',
            'status' => AttendanceStatus::PendingVerification,
            'recorded_at' => now(),
        ]);

        $this->assertFalse($attendance->canBeVerifiedBy($foreignManager));
        $this->assertFalse($attendance->canBeVerifiedBy($hr));
        $this->assertTrue($attendance->canBeVerifiedBy($manager));
        $attendance->approveException($manager);
        $this->assertSame(AttendanceStatus::Normal, $attendance->fresh()->status);

        $this->actingAs($hr);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->assertFalse(AttendanceRequestResource::canCreate());
        $this->assertFalse(AttendanceRequestResource::canEdit($request));
        $this->assertFalse(AttendanceRequestResource::canDelete($request));
    }

    public function test_terminal_leave_and_duty_are_immutable(): void
    {
        $this->seed();
        Notification::fake();
        $employee = User::query()->where('nip', 'NIP006')->firstOrFail();
        $hr = User::query()->where('role', UserRole::HrAdmin)->firstOrFail();
        $leave = LeaveRequest::create([
            'user_id' => $employee->id,
            'type' => LeaveType::PaidLeave,
            'start_date' => '2030-08-09',
            'end_date' => '2030-08-09',
            'reason' => 'Keluarga',
            'status' => LeaveStatus::Pending,
        ]);
        $leave->approve($hr);

        foreach ([
            fn () => $leave->update(['reason' => 'Diubah']),
            fn () => $leave->delete(),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('Approved leave should be immutable.');
            } catch (BusinessRuleException) {
                $this->assertModelExists($leave);
            }
        }

        $request = $this->pendingRequest($employee, '2030-08-10');
        $request->reject($employee->manager);

        foreach ([
            fn () => $request->update(['destination_name' => 'Diubah']),
            fn () => $request->delete(),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('Rejected duty should be immutable.');
            } catch (BusinessRuleException) {
                $this->assertModelExists($request);
            }
        }
    }

    public function test_attendance_evidence_is_limited_to_owner_direct_manager_and_hr(): void
    {
        Storage::fake('local');
        $this->seed();
        $employee = User::query()->where('nip', 'NIP006')->firstOrFail();
        $manager = $employee->manager;
        $foreignManager = User::query()->where('nip', 'NIP005')->firstOrFail();
        $hr = User::query()->where('role', UserRole::HrAdmin)->firstOrFail();
        Storage::disk('local')->put('attendance/private-selfie.jpg', 'image');
        $attendance = Attendance::create([
            'user_id' => $employee->id,
            'type' => AttendanceType::CheckIn,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_to_target_meters' => 0,
            'address_snapshot' => 'Kantor',
            'photo_path' => 'attendance/private-selfie.jpg',
            'status' => AttendanceStatus::Normal,
            'recorded_at' => now(),
        ]);

        foreach ([$employee, $manager, $hr] as $actor) {
            $this->actingAs($actor)
                ->get(route('attendance.evidence', $attendance))
                ->assertOk();
        }

        $this->actingAs($foreignManager)
            ->get(route('attendance.evidence', $attendance))
            ->assertForbidden();
    }

    public function test_status_changes_require_locked_workflow_methods(): void
    {
        $this->seed();
        Notification::fake();
        $employee = User::query()->where('nip', 'NIP006')->firstOrFail();
        $manager = $employee->manager;
        $foreignManager = User::query()->where('nip', 'NIP005')->firstOrFail();
        $hr = User::query()->where('role', UserRole::HrAdmin)->firstOrFail();
        $request = $this->pendingRequest($employee, '2030-09-01');
        $leave = LeaveRequest::create([
            'user_id' => $employee->id,
            'type' => LeaveType::PaidLeave,
            'start_date' => '2030-09-02',
            'end_date' => '2030-09-02',
            'reason' => 'Keluarga',
            'status' => LeaveStatus::Approved,
            'approved_by' => $hr->id,
            'approved_at' => now(),
        ]);
        $attendance = Attendance::create([
            'user_id' => $employee->id,
            'type' => AttendanceType::CheckOut,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_to_target_meters' => 500,
            'address_snapshot' => 'Lokasi klien',
            'photo_path' => 'attendance/exception.jpg',
            'is_radius_exception' => true,
            'exception_reason' => 'Selesai di lokasi klien.',
            'status' => AttendanceStatus::PendingVerification,
            'recorded_at' => now(),
        ]);

        $this->assertSame(LeaveStatus::Pending, $leave->status);
        $this->assertNull($leave->approved_by);
        $this->assertNull($leave->approved_at);

        $blocked = 0;

        foreach ([
            fn () => $request->update(['status' => AttendanceRequestStatus::Approved]),
            fn () => $leave->update(['status' => LeaveStatus::Approved]),
            fn () => $attendance->update(['status' => AttendanceStatus::Normal]),
        ] as $mutation) {
            try {
                $mutation();
            } catch (BusinessRuleException) {
                $blocked++;
            }
        }

        $this->assertSame(3, $blocked);
        $attendance->timeoutException();
        $leave->approve($hr);

        $otherEmployee = User::query()->where('nip', 'NIP008')->firstOrFail();
        $managerWins = Attendance::create([
            'user_id' => $otherEmployee->id,
            'type' => AttendanceType::CheckOut,
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance_to_target_meters' => 500,
            'address_snapshot' => 'Lokasi klien',
            'photo_path' => 'attendance/manager-wins.jpg',
            'is_radius_exception' => true,
            'exception_reason' => 'Selesai di lokasi klien.',
            'status' => AttendanceStatus::PendingVerification,
            'recorded_at' => now(),
        ]);
        $staleAttendance = $managerWins->fresh();
        $managerWins->approveException($otherEmployee->manager);
        $staleAttendance->timeoutException();
        $this->assertSame(AttendanceStatus::Normal, $staleAttendance->status);

        $cancelled = $this->pendingRequest($employee, '2030-09-03');

        try {
            $cancelled->cancel($foreignManager);
            $this->fail('Foreign manager should not cancel employee duty.');
        } catch (BusinessRuleException) {
            $this->assertSame(AttendanceRequestStatus::Pending, $cancelled->fresh()->status);
        }

        $cancelled->cancel($employee);
        $this->assertSame(AttendanceRequestStatus::Cancelled, $cancelled->status);

        $decided = $this->pendingRequest($employee, '2030-09-04');
        $stale = $decided->fresh();
        $decided->approve($manager);

        try {
            $stale->reject($manager);
            $this->fail('Stale decision should not overwrite approved duty.');
        } catch (BusinessRuleException) {
            $this->assertSame(AttendanceRequestStatus::Approved, $stale->fresh()->status);
        }
    }

    private function pendingRequest(User $employee, string $date): AttendanceRequest
    {
        return AttendanceRequest::create([
            'user_id' => $employee->id,
            'created_by' => $employee->id,
            'flow_type' => FlowType::BottomUp,
            'destination_name' => 'Kantor Klien',
            'destination_address' => 'Jakarta',
            'target_latitude' => -6.1754,
            'target_longitude' => 106.8272,
            'allowed_radius_meters' => 100,
            'duty_start_datetime' => "{$date} 08:00:00",
            'duty_end_datetime' => "{$date} 17:00:00",
            'reason' => 'Meeting',
            'status' => AttendanceRequestStatus::Pending,
        ]);
    }
}
