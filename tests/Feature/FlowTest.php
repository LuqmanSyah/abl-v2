<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\MentoringStatus;
use App\Enums\ReviewType;
use App\Enums\TrainingRequestStatus;
use App\Enums\UserRole;
use App\Models\CareerGoal;
use App\Models\Competency;
use App\Models\DutyTrip;
use App\Models\EmployeeCompetency;
use App\Models\EmployeeKpi;
use App\Models\KpiIndicator;
use App\Models\Mentoring;
use App\Models\PerformanceReview;
use App\Models\Position;
use App\Models\PositionCompetency;
use App\Models\ReviewPeriod;
use App\Models\Training;
use App\Models\TrainingRequest;
use App\Models\Unit;
use App\Models\User;
use App\Services\AttendanceRecorder;
use App\Services\CareerGapService;
use App\Services\MeritCalculator;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_to_end_sdm_flow(): void
    {
        // ===== 1. HR SETUP: unit → position → user =====
        $unit = Unit::create(['name' => 'Teknologi', 'code' => 'TI']);
        $staffPos = Position::create(['unit_id' => $unit->id, 'name' => 'Staf', 'level' => 1]);
        $supPos = Position::create(['unit_id' => $unit->id, 'name' => 'Supervisor', 'level' => 3]);

        $competency = Competency::create(['name' => 'Kepemimpinan']);
        PositionCompetency::create(['position_id' => $supPos->id, 'competency_id' => $competency->id, 'required_level' => 4]);

        $training = Training::create(['competency_id' => $competency->id, 'name' => 'Leadership Dasar', 'type' => 'internal', 'is_active' => true]);

        $hr = User::factory()->create(['role' => UserRole::Hr, 'name' => 'HR Admin']);
        $manager = User::factory()->create(['role' => UserRole::Manager, 'unit_id' => $unit->id, 'position_id' => $supPos->id, 'name' => 'Manager TI']);
        $employee = User::factory()->create([
            'role' => UserRole::Employee, 'unit_id' => $unit->id,
            'position_id' => $staffPos->id, 'manager_id' => $manager->id,
            'name' => 'Staf TI',
        ]);

        // ===== 2. MANAGER buat Perintah Dinas =====
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

        $this->assertDatabaseHas('duty_trips', ['id' => $trip->id, 'status' => 'approved']);

        // ===== 3. Employee ABSENSI (GPS dalam radius) =====
        Storage::fake('local');
        $first = app(AttendanceRecorder::class)->record($trip, $employee, [
            'captured_at' => now()->toIso8601String(),
            'latitude' => -6.1754,
            'longitude' => 106.8272,
            'accuracy_meters' => 10,
        ], 'attendance/flow-photo.jpg');

        $this->assertSame(AttendanceStatus::Valid, $first->status);
        $this->assertSame(DutyTripStatus::Approved, $trip->fresh()->status);

        // ===== 4. PERIODE + KPI indicator =====
        $period = ReviewPeriod::create([
            'name' => 'Semester Flow',
            'starts_at' => today()->subMonth(), 'ends_at' => today()->addMonth(),
            'kpi_weight' => 40, 'discipline_weight' => 20, 'manager_weight' => 20,
            'review_360_weight' => 20, 'base_bonus' => 1_000_000, 'is_active' => true,
        ]);
        $indicator = KpiIndicator::create(['review_period_id' => $period->id, 'name' => 'Kualitas hasil', 'weight' => 100]);

        // ===== 5. KPI + 360 REVIEWS =====
        $kpi = EmployeeKpi::create([
            'review_period_id' => $period->id, 'kpi_indicator_id' => $indicator->id,
            'employee_id' => $employee->id, 'manager_id' => $manager->id,
            'target' => 100, 'achievement' => 85,
        ]);

        PerformanceReview::create([
            'review_period_id' => $period->id, 'reviewer_id' => $manager->id,
            'reviewee_id' => $employee->id, 'type' => ReviewType::ManagerToEmployee,
            'score' => 4, 'submitted_at' => now(),
        ]);
        PerformanceReview::create([
            'review_period_id' => $period->id, 'reviewer_id' => $employee->id,
            'reviewee_id' => $manager->id, 'type' => ReviewType::EmployeeToManager,
            'score' => 5, 'submitted_at' => now(),
        ]);

        // ===== 6. HR hitung MERIT =====
        $result = app(MeritCalculator::class)->calculate($period, $employee);
        $this->assertNotNull($result->total_score);
        $this->assertNull($result->manager_verified_at);

        // Hitung ulang sebelum verifikasi → boleh
        $recalc = app(MeritCalculator::class)->calculate($period, $employee);
        $this->assertSame($result->id, $recalc->id);

        // ===== 7. MANAGER verifikasi → HR publish =====
        $result->verifyByManager($manager);
        $this->assertNotNull($result->fresh()->manager_verified_at);

        $result->verifyByHr($hr);
        $this->assertNotNull($result->fresh()->published_at);

        // Hitung ulang setelah publish → ditolak
        try {
            app(MeritCalculator::class)->calculate($period, $employee);
            $this->fail('Merit sudah dipublish.');
        } catch (DomainException) {
            $this->assertTrue(true);
        }

        // ===== 8. CAREER GOAL + GAP ANALYSIS =====
        $goal = CareerGoal::create(['user_id' => $employee->id, 'target_position_id' => $supPos->id]);
        EmployeeCompetency::create(['user_id' => $employee->id, 'competency_id' => $competency->id, 'level' => 2, 'assessed_at' => today()]);

        $analysis = app(CareerGapService::class)->analyze($goal);
        $this->assertSame(2, $analysis->first()['gap']);

        // ===== 9. TRAINING — employee request jalur =====
        $req = TrainingRequest::create([
            'user_id' => $employee->id, 'training_id' => $training->id,
            'manager_id' => $manager->id, 'reason' => 'Pengembangan', 'requested_at' => now(),
        ]);
        $req->approveByManager($manager, 'Setuju');
        $req->verifyByHr($hr);
        $req->complete($hr, 'Lulus');
        $this->assertSame(TrainingRequestStatus::Completed, $req->fresh()->status);

        // TRAINING — manager rekomendasi jalur
        $second = Training::create(['name' => 'Komunikasi', 'type' => 'internal', 'is_active' => true]);
        $rec = TrainingRequest::recommendByManager($manager, $employee, $second, $result, 'Rekomendasi');
        $this->assertSame(TrainingRequestStatus::Approved, $rec->status);

        // ===== 10. MENTORING =====
        $mentoring = Mentoring::create([
            'employee_id' => $employee->id, 'manager_id' => $manager->id,
            'topic' => 'Karier', 'target' => 'Promosi', 'requested_at' => now()->addDay(),
        ]);
        $mentoring->approve($manager, now()->addDays(2), 'Siapkan agenda');
        $mentoring->complete($manager, 'Target jelas', 'Evaluasi bulan depan');
        $this->assertSame(MentoringStatus::Completed, $mentoring->fresh()->status);

        // ===== 11. HR REPORT =====
        $this->actingAs($hr)
            ->get(route('hr.reports.index', ['unit_id' => $unit->id]))
            ->assertOk()
            ->assertSee($employee->name);

        // ===== 12. FINAL ASSERTIONS =====
        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseCount('duty_trips', 1);
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('employee_kpis', 1);
        $this->assertDatabaseCount('performance_reviews', 2);
        $this->assertDatabaseCount('merit_results', 1);
        $this->assertDatabaseCount('career_goals', 1);
        $this->assertDatabaseCount('training_requests', 2);
        $this->assertDatabaseCount('mentorings', 1);
        $this->assertDatabaseHas('activity_logs', ['action' => 'duty_trip.assigned']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'attendance.created']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'kpi.created']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'merit.calculated']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'merit.manager_verified']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'merit.hr_published']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'training.requested']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'training.manager_approved']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'training.hr_verified']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'training.completed']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'training.recommended']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'mentoring.requested']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'mentoring.approved']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'mentoring.completed']);
    }
}
