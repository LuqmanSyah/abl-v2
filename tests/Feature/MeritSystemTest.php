<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\ReviewType;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Models\EmployeeKpi;
use App\Models\KpiIndicator;
use App\Models\PerformanceReview;
use App\Models\ReviewPeriod;
use App\Models\Unit;
use App\Models\User;
use App\Services\MeritCalculator;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeritSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_merit_formula_and_two_stage_publication(): void
    {
        $unit = Unit::create(['name' => 'Teknologi', 'code' => 'TI']);
        $manager = User::factory()->create(['role' => UserRole::Manager, 'unit_id' => $unit->id]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'unit_id' => $unit->id, 'manager_id' => $manager->id]);
        $peer = User::factory()->create(['role' => UserRole::Employee, 'unit_id' => $unit->id, 'manager_id' => $manager->id]);
        $hr = User::factory()->create(['role' => UserRole::Hr]);
        $period = ReviewPeriod::create([
            'name' => 'Semester 1', 'starts_at' => now()->startOfMonth(), 'ends_at' => now()->endOfMonth(),
            'kpi_weight' => 40, 'discipline_weight' => 20, 'manager_weight' => 20,
            'review_360_weight' => 20, 'base_bonus' => 1_000_000, 'is_active' => true,
        ]);
        $first = KpiIndicator::create(['review_period_id' => $period->id, 'name' => 'Kualitas', 'weight' => 60]);
        $second = KpiIndicator::create(['review_period_id' => $period->id, 'name' => 'Kecepatan', 'weight' => 40]);
        EmployeeKpi::create(['review_period_id' => $period->id, 'kpi_indicator_id' => $first->id, 'employee_id' => $employee->id, 'manager_id' => $manager->id, 'target' => 100, 'achievement' => 100]);
        EmployeeKpi::create(['review_period_id' => $period->id, 'kpi_indicator_id' => $second->id, 'employee_id' => $employee->id, 'manager_id' => $manager->id, 'target' => 100, 'achievement' => 50]);
        $this->attendance($employee, $manager, AttendanceStatus::Valid, '30f26f3e-b3b3-49f6-9bcb-c31ec9862201');
        $this->attendance($employee, $manager, AttendanceStatus::OutsideRadius, '30f26f3e-b3b3-49f6-9bcb-c31ec9862202');
        PerformanceReview::create(['review_period_id' => $period->id, 'reviewer_id' => $manager->id, 'reviewee_id' => $employee->id, 'type' => ReviewType::ManagerToEmployee, 'score' => 4, 'submitted_at' => now()]);
        PerformanceReview::create(['review_period_id' => $period->id, 'reviewer_id' => $peer->id, 'reviewee_id' => $employee->id, 'type' => ReviewType::Peer, 'score' => 3, 'submitted_at' => now()]);

        $result = app(MeritCalculator::class)->calculate($period, $employee);

        $this->assertEquals(80, $result->kpi_score);
        $this->assertEquals(50, $result->discipline_score);
        $this->assertEquals(80, $result->manager_score);
        $this->assertEquals(60, $result->review_360_score);
        $this->assertEquals(70, $result->total_score);
        $this->assertEquals(700_000, $result->estimated_bonus);
        $this->assertFalse($employee->meritResults()->visibleTo($employee)->exists());

        $result->verifyByManager($manager);
        $this->assertFalse($employee->meritResults()->visibleTo($employee)->exists());
        $result->verifyByHr($hr);

        $this->assertNotNull($result->fresh()->published_at);
        $this->assertTrue($employee->meritResults()->visibleTo($employee)->exists());
    }

    public function test_merit_weights_must_total_one_hundred(): void
    {
        $this->expectException(DomainException::class);
        ReviewPeriod::create([
            'name' => 'Tidak valid', 'starts_at' => today(), 'ends_at' => today()->addMonth(),
            'kpi_weight' => 50, 'discipline_weight' => 20, 'manager_weight' => 20,
            'review_360_weight' => 20, 'base_bonus' => 0,
        ]);
    }

    public function test_review_relationship_is_enforced(): void
    {
        $firstUnit = Unit::create(['name' => 'Satu', 'code' => 'SATU']);
        $secondUnit = Unit::create(['name' => 'Dua', 'code' => 'DUA']);
        $reviewer = User::factory()->create(['role' => UserRole::Employee, 'unit_id' => $firstUnit->id]);
        $stranger = User::factory()->create(['role' => UserRole::Employee, 'unit_id' => $secondUnit->id]);
        $period = ReviewPeriod::create([
            'name' => 'Periode', 'starts_at' => today(), 'ends_at' => today()->addMonth(),
            'kpi_weight' => 40, 'discipline_weight' => 20, 'manager_weight' => 20,
            'review_360_weight' => 20, 'base_bonus' => 0,
        ]);

        $this->expectException(DomainException::class);
        PerformanceReview::create([
            'review_period_id' => $period->id, 'reviewer_id' => $reviewer->id,
            'reviewee_id' => $stranger->id, 'type' => ReviewType::Peer,
            'score' => 5, 'submitted_at' => now(),
        ]);
    }

    private function attendance(User $employee, User $manager, AttendanceStatus $status, string $uuid): Attendance
    {
        $trip = DutyTrip::create([
            'employee_id' => $employee->id, 'manager_id' => $manager->id, 'destination' => 'Dinas',
            'purpose' => 'Tugas', 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(),
            'location_name' => 'Kantor', 'address' => 'Jakarta', 'latitude' => -6.2,
            'longitude' => 106.8, 'radius_meters' => 100, 'status' => DutyTripStatus::Approved,
        ]);

        return Attendance::create([
            'client_uuid' => $uuid, 'duty_trip_id' => $trip->id, 'employee_id' => $employee->id,
            'captured_at' => now(), 'latitude' => -6.2, 'longitude' => 106.8,
            'distance_meters' => 0, 'photo_path' => 'attendance/test.jpg', 'status' => $status,
        ]);
    }
}
