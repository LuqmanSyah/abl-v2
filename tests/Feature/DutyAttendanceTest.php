<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Models\DutyTrip;
use App\Models\User;
use App\Services\AttendanceRecorder;
use App\Support\GeoDistance;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DutyAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_haversine_returns_distance_in_meters(): void
    {
        $this->assertSame(111, GeoDistance::meters(-6.1754, 106.8272, -6.1744, 106.8272));
    }

    public function test_only_assigned_manager_can_change_or_cancel_future_trip(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $trip->update(['starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2)]);
        $otherManager = User::factory()->create(['role' => UserRole::Manager]);

        $this->assertFalse($trip->canBeChangedBy($otherManager));
        $this->assertTrue($trip->canBeChangedBy($manager));

        try {
            $trip->cancel($otherManager);
            $this->fail('Atasan lain seharusnya ditolak.');
        } catch (DomainException) {
            $this->assertSame(DutyTripStatus::Approved, $trip->status);
        }

        $trip->cancel($manager);
        $this->assertSame(DutyTripStatus::Cancelled, $trip->fresh()->status);
    }

    public function test_manager_cannot_assign_another_managers_subordinate(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $otherManager = User::factory()->create(['role' => UserRole::Manager]);
        $otherEmployee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $otherManager->id]);

        $this->expectException(DomainException::class);
        $trip->update(['employee_id' => $otherEmployee->id]);
    }

    public function test_attendance_is_valid_inside_radius_and_idempotent(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $payload = [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862217',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ];

        $recorder = app(AttendanceRecorder::class);
        $first = $recorder->record($trip, $employee, $payload, 'attendance/photo.jpg');
        $second = $recorder->record($trip, $employee, $payload, 'attendance/duplicate.jpg');

        $this->assertSame(AttendanceStatus::Valid, $first->status);
        $this->assertTrue($first->is($second));
        $this->assertSame(DutyTripStatus::Completed, $trip->fresh()->status);
        $this->assertDatabaseCount('attendances', 1);

        $this->expectException(DomainException::class);
        $trip->update(['latitude' => -7.0]);
    }

    public function test_outside_radius_and_poor_accuracy_are_flagged(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $attendance = app(AttendanceRecorder::class)->record($trip, $employee, [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862218',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1800,
            'longitude' => 106.8272,
            'accuracy_meters' => 150,
        ], 'attendance/photo.jpg');

        $this->assertSame(AttendanceStatus::NeedsReview, $attendance->status);
        $this->assertTrue($attendance->mock_location_suspected);
    }

    public function test_outside_radius_and_late_attendance_are_classified(): void
    {
        [$employee, $manager, $trip] = $this->trip();
        $outside = app(AttendanceRecorder::class)->record($trip, $employee, [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862219',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1800,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/outside.jpg');

        [$lateEmployee, $lateManager, $lateTrip] = $this->trip();
        $lateTrip->update(['starts_at' => now()->subHours(3), 'ends_at' => now()->subHours(2)]);
        $late = app(AttendanceRecorder::class)->record($lateTrip, $lateEmployee, [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862220',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/late.jpg');

        $this->assertSame(AttendanceStatus::OutsideRadius, $outside->status);
        $this->assertSame(AttendanceStatus::Late, $late->status);
    }

    public function test_sync_endpoint_is_idempotent(): void
    {
        Storage::fake('local');
        [$employee, $manager, $trip] = $this->trip();
        $payload = [
            'client_uuid' => '20f26f3e-b3b3-49f6-9bcb-c31ec9862221',
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ];
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        $this->actingAs($employee)
            ->postJson(route('attendance.store', $trip), [...$payload, 'photo' => UploadedFile::fake()->createWithContent('photo.png', $png)])
            ->assertCreated();
        $this->postJson(route('attendance.store', $trip), [...$payload, 'photo' => UploadedFile::fake()->createWithContent('photo.png', $png)])
            ->assertOk();

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_manager_assigns_trip_and_employee_captures_attendance(): void
    {
        [$employee, $manager, $trip] = $this->trip();

        $this->actingAs($manager)
            ->get('/atasan/duty-trips/create')
            ->assertOk()
            ->assertSee('Pegawai yang ditugaskan');

        $this->actingAs($employee)
            ->get('/pegawai/duty-trips/create')
            ->assertForbidden();

        $this->actingAs($employee)
            ->get(route('attendance.capture', $trip))
            ->assertOk()
            ->assertSee('Ambil GPS dan Simpan');
    }

    /** @return array{User, User, DutyTrip} */
    private function trip(): array
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $manager->id]);
        $trip = DutyTrip::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'destination' => 'Rapat koordinasi',
            'purpose' => 'Koordinasi proyek',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'location_name' => 'Monas',
            'address' => 'Jakarta Pusat',
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'radius_meters' => 100,
            'status' => DutyTripStatus::Approved,
            'approved_at' => now(),
        ]);

        return [$employee, $manager, $trip];
    }
}
