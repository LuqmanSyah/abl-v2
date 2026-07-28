<?php

namespace Tests\Feature;

use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Filament\Resources\DutyTrips\Pages\CreateDutyTrip;
use App\Models\DutyTrip;
use App\Models\User;
use DomainException;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DutyTripManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_trip_for_subordinate(): void
    {
        [$employee, $manager] = $this->users();

        $trip = DutyTrip::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'destination' => 'Test Trip',
            'purpose' => 'Test Purpose',
            'location_name' => 'Kantor',
            'address' => 'Jakarta',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'radius_meters' => 100,
            'status' => DutyTripStatus::Approved,
            'approved_at' => now(),
        ]);

        $this->assertTrue($trip->exists);
        $this->assertSame(DutyTripStatus::Approved, $trip->status);
    }

    public function test_schedule_form_uses_dates_only(): void
    {
        [$employee, $manager] = $this->users();

        $this->actingAs($manager);
        Filament::setCurrentPanel(Filament::getPanel('manager'));

        Livewire::test(CreateDutyTrip::class)
            ->assertFormFieldDoesNotExist('start_time')
            ->assertFormFieldDoesNotExist('end_time')
            ->fillForm([
                'employee_id' => $employee->id,
                'destination' => 'Dinas luar kota',
                'purpose' => 'Koordinasi proyek',
                'starts_at' => '2030-08-01',
                'ends_at' => '2030-08-03',
                'location_name' => 'Kantor cabang',
                'address' => 'Jakarta',
                'latitude' => -6.1754,
                'longitude' => 106.8272,
                'radius_meters' => 100,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $trip = DutyTrip::where('destination', 'Dinas luar kota')->firstOrFail();

        $this->assertSame('2030-08-01 00:00:00', $trip->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2030-08-03 23:59:59', $trip->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_manager_can_cancel_future_trip(): void
    {
        [$employee, $manager] = $this->users();
        $trip = DutyTrip::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'destination' => 'Test',
            'purpose' => 'Test',
            'location_name' => 'Kantor',
            'address' => 'Jakarta',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'radius_meters' => 100,
            'status' => DutyTripStatus::Approved,
            'approved_at' => now(),
        ]);

        $trip->cancel($manager);
        $this->assertSame(DutyTripStatus::Cancelled, $trip->fresh()->status);
    }

    public function test_other_manager_cannot_cancel_trip(): void
    {
        [$employee, $manager] = $this->users();
        $other = User::factory()->create(['role' => UserRole::Manager]);
        $trip = DutyTrip::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'destination' => 'Test',
            'purpose' => 'Test',
            'location_name' => 'Kantor',
            'address' => 'Jakarta',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'radius_meters' => 100,
            'status' => DutyTripStatus::Approved,
            'approved_at' => now(),
        ]);

        $this->expectException(DomainException::class);
        $trip->cancel($other);
    }

    public function test_employee_can_only_see_own_trips(): void
    {
        [$employee, $manager] = $this->users();
        $otherEmployee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $manager->id]);

        foreach (['A', 'B'] as $dest) {
            DutyTrip::create(['employee_id' => $employee->id, 'manager_id' => $manager->id, 'destination' => $dest, 'purpose' => 'Test', 'location_name' => 'Kantor', 'address' => 'Jakarta', 'starts_at' => now(), 'ends_at' => now()->addHour(), 'latitude' => -6.1754, 'longitude' => 106.8272, 'radius_meters' => 100, 'status' => DutyTripStatus::Approved, 'approved_at' => now()]);
        }
        DutyTrip::create(['employee_id' => $otherEmployee->id, 'manager_id' => $manager->id, 'destination' => 'C', 'purpose' => 'Test', 'location_name' => 'Kantor', 'address' => 'Jakarta', 'starts_at' => now(), 'ends_at' => now()->addHour(), 'latitude' => -6.1754, 'longitude' => 106.8272, 'radius_meters' => 100, 'status' => DutyTripStatus::Approved, 'approved_at' => now()]);

        $trips = DutyTrip::visibleTo($employee)->get();
        $this->assertCount(2, $trips);
    }

    public function test_manager_can_see_subordinate_trips(): void
    {
        [$employee, $manager] = $this->users();
        $otherManager = User::factory()->create(['role' => UserRole::Manager]);
        $otherEmployee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $otherManager->id]);

        foreach (['A', 'B'] as $dest) {
            DutyTrip::create(['employee_id' => $employee->id, 'manager_id' => $manager->id, 'destination' => $dest, 'purpose' => 'Test', 'location_name' => 'Kantor', 'address' => 'Jakarta', 'starts_at' => now(), 'ends_at' => now()->addHour(), 'latitude' => -6.1754, 'longitude' => 106.8272, 'radius_meters' => 100, 'status' => DutyTripStatus::Approved, 'approved_at' => now()]);
        }
        DutyTrip::create(['employee_id' => $otherEmployee->id, 'manager_id' => $otherManager->id, 'destination' => 'C', 'purpose' => 'Test', 'location_name' => 'Kantor', 'address' => 'Jakarta', 'starts_at' => now(), 'ends_at' => now()->addHour(), 'latitude' => -6.1754, 'longitude' => 106.8272, 'radius_meters' => 100, 'status' => DutyTripStatus::Approved, 'approved_at' => now()]);

        $trips = DutyTrip::visibleTo($manager)->get();
        $this->assertCount(2, $trips);
    }

    public function test_cannot_cancel_trip_after_attendance(): void
    {
        [$employee, $manager] = $this->users();
        $trip = DutyTrip::create([
            'employee_id' => $employee->id,
            'manager_id' => $manager->id,
            'destination' => 'Test',
            'purpose' => 'Test',
            'location_name' => 'Kantor',
            'address' => 'Jakarta',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'radius_meters' => 100,
            'status' => DutyTripStatus::Approved,
            'approved_at' => now(),
        ]);

        $trip->attendances()->create([
            'employee_id' => $employee->id,
            'duty_trip_id' => $trip->id,
            'status' => 'valid',
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'distance_meters' => 10,
            'captured_at' => now(),
            'attendance_date' => today(),
            'photo_path' => 'test.jpg',
        ]);

        $this->expectException(DomainException::class);
        $trip->cancel($manager);
    }

    /** @return array{0: User, 1: User} */
    private function users(): array
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'manager_id' => $manager->id,
        ]);

        return [$employee, $manager];
    }
}
