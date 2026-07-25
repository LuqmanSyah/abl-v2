<?php

namespace Tests\Feature;

use App\Enums\AttendanceRequestStatus;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\FlowType;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Models\AttendanceRequest;
use App\Models\BranchOffice;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceService;
use App\Services\GoogleMapsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_office_check_in_within_radius_is_recorded_once_per_day(): void
    {
        $user = $this->employee();
        $maps = Mockery::mock(GoogleMapsService::class);
        $maps->shouldReceive('distance')->twice()->andReturn(20);
        $service = new AttendanceService($maps);

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
        $service = $this->serviceReturning(101);

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
        $service = $this->serviceReturning(10, 10);

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

    public function test_early_check_in_is_rejected_and_past_cutoff_is_alfa(): void
    {
        $user = $this->employee();
        $service = $this->serviceReturning(10);

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

    public function test_google_failure_falls_back_to_haversine(): void
    {
        $maps = Mockery::mock(GoogleMapsService::class);
        $maps->shouldReceive('distance')->once()->andThrow(new RuntimeException('API unavailable'));

        $attendance = (new AttendanceService($maps))->record(
            $this->employee(),
            AttendanceType::CheckIn,
            -6.2088,
            106.8456,
            'attendance/fallback.jpg',
            recordedAt: now()->setDate(2026, 8, 1)->setTime(8, 0),
        );

        $this->assertTrue($attendance->is_fallback);
        $this->assertEquals(0, $attendance->distance_to_target_meters);
    }

    public function test_outside_radius_check_out_requires_exception_and_is_pending(): void
    {
        $user = $this->employee();
        $service = $this->serviceReturning(500, 500);

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
            $this->assertDatabaseCount('attendances', 0);
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
    }

    public function test_last_day_check_out_at_home_branch_is_allowed(): void
    {
        $user = $this->employee();
        $request = $this->attendanceRequest($user, AttendanceRequestStatus::Approved, '2026-08-01');
        $maps = Mockery::mock(GoogleMapsService::class);
        $maps->shouldReceive('distance')->twice()->andReturn(500, 10);

        $attendance = (new AttendanceService($maps))->record(
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

    public function test_google_distance_matrix_retries_three_times(): void
    {
        config()->set('services.google_maps.key', 'test-key');
        Http::fakeSequence()
            ->push([], 500)
            ->push([], 500)
            ->push([
                'status' => 'OK',
                'rows' => [['elements' => [[
                    'status' => 'OK',
                    'distance' => ['value' => 42],
                ]]]],
            ]);

        $this->assertSame(42, (new GoogleMapsService(0))->distance(-6.2, 106.8, -6.1, 106.9));
        Http::assertSentCount(3);
    }

    public function test_attendance_form_renders_gps_capture_and_photo_fields(): void
    {
        $user = $this->employee();
        $user->update(['role' => UserRole::Manager]);

        $this->actingAs($user)
            ->get(route('filament.admin.resources.attendances.create'))
            ->assertOk()
            ->assertSee('Ambil Lokasi GPS')
            ->assertSee('Foto');
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

        return User::factory()->create([
            'position_id' => $position->id,
            'work_schedule_id' => $schedule->id,
            'branch_office_id' => $branch->id,
        ]);
    }

    private function attendanceRequest(
        User $user,
        AttendanceRequestStatus $status,
        string $date,
    ): AttendanceRequest {
        return AttendanceRequest::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'flow_type' => FlowType::BottomUp,
            'destination_name' => 'Client Office',
            'destination_address' => 'Jakarta',
            'target_latitude' => -6.1754,
            'target_longitude' => 106.8272,
            'allowed_radius_meters' => 100,
            'duty_start_datetime' => "{$date} 08:00:00",
            'duty_end_datetime' => "{$date} 17:00:00",
            'reason' => 'Meeting',
            'status' => $status,
            'approved_by' => $status === AttendanceRequestStatus::Approved ? $user->id : null,
        ]);
    }

    private function serviceReturning(int ...$distances): AttendanceService
    {
        $maps = Mockery::mock(GoogleMapsService::class);
        $expectation = $maps->shouldReceive('distance')->times(count($distances));
        $expectation->andReturn(...$distances);

        return new AttendanceService($maps);
    }
}
