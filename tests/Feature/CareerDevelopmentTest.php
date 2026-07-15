<?php

namespace Tests\Feature;

use App\Enums\MentoringStatus;
use App\Enums\TrainingRequestStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\CareerGoal;
use App\Models\Competency;
use App\Models\EmployeeCompetency;
use App\Models\Mentoring;
use App\Models\Position;
use App\Models\PositionCompetency;
use App\Models\Training;
use App\Models\TrainingRequest;
use App\Models\Unit;
use App\Models\User;
use App\Services\CareerGapService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareerDevelopmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_gap_analysis_returns_training_and_mentoring_recommendations(): void
    {
        [$employee, $manager, $hr, $current, $target] = $this->organization();
        $leadership = Competency::create(['name' => 'Kepemimpinan']);
        $technical = Competency::create(['name' => 'Teknis']);
        PositionCompetency::create(['position_id' => $target->id, 'competency_id' => $leadership->id, 'required_level' => 4]);
        PositionCompetency::create(['position_id' => $target->id, 'competency_id' => $technical->id, 'required_level' => 3]);
        EmployeeCompetency::create(['user_id' => $employee->id, 'competency_id' => $leadership->id, 'level' => 2, 'assessed_at' => today()]);
        EmployeeCompetency::create(['user_id' => $employee->id, 'competency_id' => $technical->id, 'level' => 3, 'assessed_at' => today()]);
        Training::create(['competency_id' => $leadership->id, 'name' => 'Leadership Dasar', 'type' => 'internal', 'is_active' => true]);
        $goal = CareerGoal::create(['user_id' => $employee->id, 'target_position_id' => $target->id]);

        $analysis = app(CareerGapService::class)->analyze($goal)->keyBy('competency');

        $this->assertSame(2, $analysis['Kepemimpinan']['gap']);
        $this->assertSame('Leadership Dasar', $analysis['Kepemimpinan']['recommendations']);
        $this->assertSame(0, $analysis['Teknis']['gap']);
        $this->assertTrue(CareerGoal::visibleTo($employee)->whereKey($goal)->exists());
        $this->assertTrue(CareerGoal::visibleTo($manager)->whereKey($goal)->exists());
        $this->assertTrue(CareerGoal::visibleTo($hr)->whereKey($goal)->exists());
    }

    public function test_training_and_mentoring_workflows_enforce_role_order(): void
    {
        [$employee, $manager, $hr] = $this->organization();
        $otherManager = User::factory()->create(['role' => UserRole::Manager]);
        $training = Training::create(['name' => 'Komunikasi', 'type' => 'internal', 'is_active' => true]);
        $request = TrainingRequest::create([
            'user_id' => $employee->id, 'training_id' => $training->id, 'manager_id' => $manager->id,
            'status' => TrainingRequestStatus::PendingManager, 'reason' => 'Perlu', 'requested_at' => now(),
        ]);

        try {
            $request->approveByManager($otherManager);
            $this->fail('Atasan lain tidak boleh menyetujui.');
        } catch (DomainException) {
            $this->assertSame(TrainingRequestStatus::PendingManager, $request->status);
        }

        $request->approveByManager($manager, 'Disetujui');
        $request->verifyByHr($hr);
        $request->complete($hr, 'Lulus');
        $this->assertSame(TrainingRequestStatus::Completed, $request->fresh()->status);

        $mentoring = Mentoring::create([
            'employee_id' => $employee->id, 'manager_id' => $manager->id,
            'topic' => 'Karier', 'target' => 'Rencana promosi', 'requested_at' => now()->addDay(),
        ]);
        $mentoring->approve($manager, now()->addDays(2), 'Siapkan agenda');
        $mentoring->complete($manager, 'Target jelas', 'Evaluasi bulan depan');

        $this->assertSame(MentoringStatus::Completed, $mentoring->fresh()->status);
        $this->assertSame(7, ActivityLog::count());
    }

    public function test_employee_cannot_create_development_record_for_colleague(): void
    {
        [$employee, $manager, $hr, $current, $target] = $this->organization();
        $colleague = User::factory()->create([
            'role' => UserRole::Employee,
            'unit_id' => $current->unit_id,
            'position_id' => $current->id,
            'manager_id' => $manager->id,
        ]);

        $this->actingAs($employee);
        $this->expectException(DomainException::class);
        CareerGoal::create(['user_id' => $colleague->id, 'target_position_id' => $target->id]);
    }

    public function test_career_resources_render_for_each_role(): void
    {
        [$employee, $manager, $hr] = $this->organization();

        $this->actingAs($employee)->get('/pegawai/career-goals')->assertOk();
        $this->get('/pegawai/trainings')->assertOk();
        $this->get('/pegawai/training-requests')->assertOk();
        $this->get('/pegawai/mentorings')->assertOk();

        $this->actingAs($manager)->get('/atasan/employee-competencies')->assertOk();
        $this->get('/atasan/training-requests')->assertOk();
        $this->get('/atasan/mentorings')->assertOk();

        $this->actingAs($hr)->get('/hr/competencies')->assertOk();
        $this->get('/hr/position-competencies')->assertOk();
        $this->get('/hr/employee-competencies')->assertOk();
        $this->get('/hr/trainings')->assertOk();
        $this->get('/hr/activity-logs')->assertOk();
    }

    /** @return array{User, User, User, Position, Position} */
    private function organization(): array
    {
        $unit = Unit::create(['name' => 'Teknologi', 'code' => 'TI']);
        $current = Position::create(['unit_id' => $unit->id, 'name' => 'Staf', 'level' => 1]);
        $target = Position::create(['unit_id' => $unit->id, 'name' => 'Supervisor', 'level' => 3]);
        $manager = User::factory()->create(['role' => UserRole::Manager, 'unit_id' => $unit->id, 'position_id' => $target->id]);
        $employee = User::factory()->create([
            'role' => UserRole::Employee, 'unit_id' => $unit->id,
            'position_id' => $current->id, 'manager_id' => $manager->id,
        ]);
        $hr = User::factory()->create(['role' => UserRole::Hr]);

        return [$employee, $manager, $hr, $current, $target];
    }
}
