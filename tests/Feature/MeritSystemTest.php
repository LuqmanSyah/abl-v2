<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\ReviewType;
use App\Enums\UserRole;
use App\Filament\Resources\EmployeeKpis\EmployeeKpiResource;
use App\Filament\Resources\KpiIndicators\KpiIndicatorResource;
use App\Filament\Resources\KpiIndicators\Pages\CreateKpiIndicator;
use App\Filament\Resources\ReviewPeriods\Pages\ListReviewPeriods;
use App\Filament\Resources\ReviewPeriods\ReviewPeriodResource;
use App\Models\ActivityLog;
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
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
        $this->attendance($employee, $manager, AttendanceStatus::Valid);
        $this->attendance($employee, $manager, AttendanceStatus::OutsideRadius);
        PerformanceReview::create(['review_period_id' => $period->id, 'reviewer_id' => $manager->id, 'reviewee_id' => $employee->id, 'type' => ReviewType::ManagerToEmployee, 'score' => 4, 'submitted_at' => now()]);
        PerformanceReview::create(['review_period_id' => $period->id, 'reviewer_id' => $peer->id, 'reviewee_id' => $employee->id, 'type' => ReviewType::Peer, 'score' => 3, 'submitted_at' => now()]);

        $result = app(MeritCalculator::class)->calculate($period, $employee);

        $this->assertEquals(80, $result->kpi_score);
        $this->assertEquals(100, $result->discipline_score);
        $this->assertEquals(80, $result->manager_score);
        $this->assertEquals(60, $result->review_360_score);
        $this->assertEquals(80, $result->total_score);
        $this->assertEquals(800_000, $result->estimated_bonus);
        $this->assertNotNull($result->calculated_at);
        $this->assertFalse($employee->meritResults()->visibleTo($employee)->exists());

        $result->verifyByManager($manager);
        $this->assertFalse($employee->meritResults()->visibleTo($employee)->exists());
        $result->verifyByHr($hr);

        $this->assertNotNull($result->fresh()->published_at);
        $this->assertTrue($employee->meritResults()->visibleTo($employee)->exists());

        $publishedAt = $result->fresh()->published_at;
        try {
            app(MeritCalculator::class)->calculate($period, $employee);
            $this->fail('Hasil merit yang sudah dipublikasikan tidak boleh dihitung ulang.');
        } catch (DomainException $exception) {
            $this->assertSame('Hasil merit sudah diverifikasi dan tidak dapat dihitung ulang.', $exception->getMessage());
        }
        $this->assertTrue($result->fresh()->published_at->equalTo($publishedAt));
        $this->assertTrue($employee->meritResults()->visibleTo($employee)->exists());
    }

    public function test_monthly_merit_can_be_recalculated_before_verification_with_update_timestamp(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $manager->id]);
        $period = ReviewPeriod::create([
            'name' => 'Juli 2026', 'starts_at' => today()->startOfMonth(), 'ends_at' => today()->endOfMonth(),
            'kpi_weight' => 100, 'discipline_weight' => 0, 'manager_weight' => 0,
            'review_360_weight' => 0, 'base_bonus' => 1_000_000,
        ]);
        $indicator = KpiIndicator::create(['review_period_id' => $period->id, 'name' => 'Kualitas', 'weight' => 100]);
        $kpi = EmployeeKpi::create([
            'review_period_id' => $period->id, 'kpi_indicator_id' => $indicator->id,
            'employee_id' => $employee->id, 'manager_id' => $manager->id,
            'target' => 100, 'achievement' => 50,
        ]);

        $first = app(MeritCalculator::class)->calculate($period, $employee);
        $firstCalculatedAt = $first->calculated_at;
        $this->assertEquals(50, $first->total_score);

        $this->travel(1)->minute();
        $kpi->update(['achievement' => 80]);
        $second = app(MeritCalculator::class)->calculate($period, $employee);

        $this->assertTrue($second->is($first));
        $this->assertEquals(80, $second->total_score);
        $this->assertTrue($second->calculated_at->greaterThan($firstCalculatedAt));
        $this->assertNull($second->manager_verified_at);
        $this->assertNull($second->published_at);
    }

    public function test_discipline_score_counts_duty_trips_without_attendance(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $manager->id]);
        $period = ReviewPeriod::create([
            'name' => 'Disiplin', 'starts_at' => today()->subDay(), 'ends_at' => today()->addDay(),
            'kpi_weight' => 0, 'discipline_weight' => 100, 'manager_weight' => 0,
            'review_360_weight' => 0, 'base_bonus' => 0,
        ]);
        $this->attendance($employee, $manager, AttendanceStatus::Valid);
        DutyTrip::create([
            'employee_id' => $employee->id, 'manager_id' => $manager->id, 'destination' => 'Tanpa absensi',
            'purpose' => 'Tugas',
            'starts_at' => today()->subDay()->addHours(8),
            'ends_at' => today()->subDay()->addHours(17),
            'location_name' => 'Kantor', 'address' => 'Jakarta', 'latitude' => -6.2,
            'longitude' => 106.8, 'radius_meters' => 100, 'status' => DutyTripStatus::Approved,
        ]);

        $result = app(MeritCalculator::class)->calculate($period, $employee);

        $this->assertEquals(50, $result->discipline_score);
        $this->assertEquals(50, $result->total_score);
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

    public function test_merit_weights_accept_numeric_form_values_totalling_one_hundred(): void
    {
        $period = ReviewPeriod::create([
            'name' => 'Valid', 'starts_at' => today(), 'ends_at' => today()->addMonth(),
            'kpi_weight' => 40.0, 'discipline_weight' => 20.0, 'manager_weight' => 20.0,
            'review_360_weight' => 20.0, 'base_bonus' => 0,
        ]);

        $this->assertSame(100, $period->kpi_weight + $period->discipline_weight + $period->manager_weight + $period->review_360_weight);
    }

    public function test_filament_shows_business_rule_failure_as_notification(): void
    {
        $hr = User::factory()->create(['role' => UserRole::Hr]);
        $period = ReviewPeriod::create([
            'name' => 'Validasi Indikator', 'starts_at' => today(), 'ends_at' => today()->addMonth(),
            'kpi_weight' => 40, 'discipline_weight' => 20, 'manager_weight' => 20,
            'review_360_weight' => 20, 'base_bonus' => 0,
        ]);
        KpiIndicator::create([
            'review_period_id' => $period->id,
            'name' => 'Indikator Lama',
            'weight' => 80,
        ]);

        $this->actingAs($hr);
        Filament::setCurrentPanel(Filament::getPanel('hr'));

        Livewire::test(CreateKpiIndicator::class)
            ->fillForm([
                'review_period_id' => $period->id,
                'name' => 'Indikator Baru',
                'weight' => 30,
            ])
            ->call('create')
            ->assertNotified('Tindakan tidak dapat diproses');

        $this->assertDatabaseCount('kpi_indicators', 1);
    }

    public function test_kpi_target_and_achievement_must_be_non_negative(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $manager->id]);
        $period = ReviewPeriod::create([
            'name' => 'Validasi KPI', 'starts_at' => today(), 'ends_at' => today()->addMonth(),
            'kpi_weight' => 40, 'discipline_weight' => 20, 'manager_weight' => 20,
            'review_360_weight' => 20, 'base_bonus' => 0,
        ]);
        $indicator = KpiIndicator::create(['review_period_id' => $period->id, 'name' => 'Kualitas', 'weight' => 100]);
        $base = [
            'review_period_id' => $period->id, 'kpi_indicator_id' => $indicator->id,
            'employee_id' => $employee->id, 'manager_id' => $manager->id,
        ];

        foreach ([
            [['target' => 0, 'achievement' => 0], 'Target KPI harus lebih dari 0.'],
            [['target' => 1, 'achievement' => -1], 'Capaian KPI tidak boleh negatif.'],
        ] as [$values, $message]) {
            try {
                EmployeeKpi::create([...$base, ...$values]);
                $this->fail($message);
            } catch (DomainException $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }

        $this->assertDatabaseCount('employee_kpis', 0);
    }

    public function test_merit_breakdown_contains_kpi_history_and_immutable_reviews(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $otherManager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $manager->id]);
        $period = ReviewPeriod::create([
            'name' => 'Audit Merit', 'starts_at' => today(), 'ends_at' => today()->addMonth(),
            'kpi_weight' => 40, 'discipline_weight' => 20, 'manager_weight' => 20,
            'review_360_weight' => 20, 'base_bonus' => 1_000_000,
        ]);
        $indicator = KpiIndicator::create(['review_period_id' => $period->id, 'name' => 'Kualitas', 'weight' => 100]);

        $this->actingAs($manager);
        $kpi = EmployeeKpi::create([
            'review_period_id' => $period->id, 'kpi_indicator_id' => $indicator->id,
            'employee_id' => $employee->id, 'manager_id' => $manager->id,
            'target' => 100, 'achievement' => 40,
        ]);
        $kpi->update(['achievement' => 80]);
        $review = PerformanceReview::create([
            'review_period_id' => $period->id, 'reviewer_id' => $manager->id,
            'reviewee_id' => $employee->id, 'type' => ReviewType::ManagerToEmployee,
            'score' => 4, 'submitted_at' => now(),
        ]);

        $result = app(MeritCalculator::class)->calculate($period, $employee);
        $breakdown = $result->breakdownForManager($manager);

        $this->assertSame('Audit Merit', $breakdown['period']);
        $this->assertCount(1, $breakdown['kpis']);
        $this->assertSame(80.0, $breakdown['kpis'][0]['score']);
        $this->assertCount(2, $breakdown['kpis'][0]['history']);
        $this->assertCount(1, $breakdown['reviews']);

        $updatedLog = ActivityLog::where('action', 'kpi.updated')->where('subject_id', $kpi->id)->sole();
        $this->assertEquals(40, $updatedLog->data['changes']['achievement']['old']);
        $this->assertEquals(80, $updatedLog->data['changes']['achievement']['new']);

        foreach ([
            fn () => $review->update(['score' => 5]),
            fn () => $review->fresh()->delete(),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('Penilaian terkirim harus immutable.');
            } catch (DomainException) {
                $this->assertDatabaseHas('performance_reviews', ['id' => $review->id, 'score' => 4]);
            }
        }

        try {
            $result->breakdownForManager($otherManager);
            $this->fail('Atasan lain tidak boleh melihat breakdown merit.');
        } catch (DomainException $exception) {
            $this->assertSame('Rincian merit tidak dapat dilihat pengguna ini.', $exception->getMessage());
        }
    }

    public function test_published_merit_locks_period_and_kpi_inputs(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $manager->id]);
        $hr = User::factory()->create(['role' => UserRole::Hr]);
        $period = ReviewPeriod::create([
            'name' => 'Terkunci', 'starts_at' => today(), 'ends_at' => today()->addMonth(),
            'kpi_weight' => 100, 'discipline_weight' => 0, 'manager_weight' => 0,
            'review_360_weight' => 0, 'base_bonus' => 1_000_000,
        ]);
        $indicator = KpiIndicator::create(['review_period_id' => $period->id, 'name' => 'Kualitas', 'weight' => 100]);
        $kpi = EmployeeKpi::create([
            'review_period_id' => $period->id, 'kpi_indicator_id' => $indicator->id,
            'employee_id' => $employee->id, 'manager_id' => $manager->id,
            'target' => 100, 'achievement' => 80,
        ]);
        $result = app(MeritCalculator::class)->calculate($period, $employee);
        $result->verifyByManager($manager);
        $result->verifyByHr($hr);

        $this->actingAs($manager);
        $this->assertFalse(EmployeeKpiResource::canEdit($kpi));
        $this->actingAs($hr);
        $this->assertFalse(ReviewPeriodResource::canEdit($period));
        $this->assertFalse(KpiIndicatorResource::canEdit($indicator));
        Filament::setCurrentPanel(Filament::getPanel('hr'));
        Livewire::test(ListReviewPeriods::class)
            ->assertTableActionHidden('calculate', $period);
        $this->get("/hr/review-periods/{$period->id}/edit")
            ->assertRedirect('/hr')
            ->assertSessionHas('filament.notifications');

        foreach ([
            [fn () => $period->update(['base_bonus' => 2_000_000]), 'Periode dengan hasil merit terpublikasi tidak dapat diubah.'],
            [fn () => $kpi->update(['achievement' => 90]), 'KPI dengan hasil merit terpublikasi tidak dapat diubah.'],
            [fn () => $indicator->update(['weight' => 90]), 'Indikator KPI pada periode terpublikasi tidak dapat diubah.'],
        ] as [$mutation, $message]) {
            try {
                $mutation();
                $this->fail($message);
            } catch (DomainException $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }

        $kpi->refresh();
        try {
            $kpi->delete();
            $this->fail('KPI terpublikasi tidak boleh dihapus.');
        } catch (DomainException $exception) {
            $this->assertSame('KPI dengan hasil merit terpublikasi tidak dapat dihapus.', $exception->getMessage());
        }

        $this->assertDatabaseHas('review_periods', ['id' => $period->id, 'base_bonus' => 1_000_000]);
        $this->assertDatabaseHas('employee_kpis', ['id' => $kpi->id, 'achievement' => 80]);
        $this->assertDatabaseHas('kpi_indicators', ['id' => $indicator->id, 'weight' => 100]);
    }

    public function test_verified_merit_cannot_be_recalculated(): void
    {
        $manager = User::factory()->create(['role' => UserRole::Manager]);
        $employee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $manager->id]);
        $period = ReviewPeriod::create([
            'name' => 'Race', 'starts_at' => today(), 'ends_at' => today()->addMonth(),
            'kpi_weight' => 100, 'discipline_weight' => 0, 'manager_weight' => 0,
            'review_360_weight' => 0, 'base_bonus' => 0,
        ]);
        $indicator = KpiIndicator::create(['review_period_id' => $period->id, 'name' => 'Kualitas', 'weight' => 100]);
        EmployeeKpi::create([
            'review_period_id' => $period->id, 'kpi_indicator_id' => $indicator->id,
            'employee_id' => $employee->id, 'manager_id' => $manager->id,
            'target' => 100, 'achievement' => 80,
        ]);
        $result = app(MeritCalculator::class)->calculate($period, $employee);
        $result->verifyByManager($manager);
        $verifiedAt = $result->manager_verified_at;

        try {
            app(MeritCalculator::class)->calculate($period, $employee);
            $this->fail('Hasil merit yang sudah diverifikasi tidak boleh dihitung ulang.');
        } catch (DomainException $exception) {
            $this->assertSame('Hasil merit sudah diverifikasi dan tidak dapat dihitung ulang.', $exception->getMessage());
        }

        $this->assertTrue($result->fresh()->manager_verified_at->equalTo($verifiedAt));
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

    private function attendance(User $employee, User $manager, AttendanceStatus $status): Attendance
    {
        $trip = DutyTrip::create([
            'employee_id' => $employee->id, 'manager_id' => $manager->id, 'destination' => 'Dinas',
            'purpose' => 'Tugas', 'starts_at' => today()->addHours(8), 'ends_at' => today()->addHours(17),
            'location_name' => 'Kantor', 'address' => 'Jakarta', 'latitude' => -6.2,
            'longitude' => 106.8, 'radius_meters' => 100, 'status' => DutyTripStatus::Completed,
        ]);

        return Attendance::create([
            'duty_trip_id' => $trip->id, 'employee_id' => $employee->id,
            'captured_at' => today()->addHours(9), 'latitude' => -6.2, 'longitude' => 106.8,
            'distance_meters' => 0, 'photo_path' => 'attendance/test.jpg', 'status' => $status,
        ]);
    }
}
