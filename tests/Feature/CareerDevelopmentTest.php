<?php

namespace Tests\Feature;

use App\Enums\MentoringStatus;
use App\Enums\TrainingRequestStatus;
use App\Enums\UserRole;
use App\Filament\Resources\MeritResults\Pages\ListMeritResults;
use App\Models\ActivityLog;
use App\Models\CareerGoal;
use App\Models\Competency;
use App\Models\EmployeeCompetency;
use App\Models\Mentoring;
use App\Models\MeritResult;
use App\Models\Position;
use App\Models\PositionCompetency;
use App\Models\ReviewPeriod;
use App\Models\Training;
use App\Models\TrainingRequest;
use App\Models\Unit;
use App\Models\User;
use App\Services\CareerGapService;
use DomainException;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    public function test_career_goal_requires_a_higher_position(): void
    {
        [$employee, , , $current] = $this->organization();

        foreach ([['Setara', $current->level], ['Lebih Rendah', $current->level - 1]] as [$name, $level]) {
            $position = Position::create([
                'unit_id' => $current->unit_id,
                'name' => $name,
                'level' => $level,
            ]);

            try {
                CareerGoal::create(['user_id' => $employee->id, 'target_position_id' => $position->id]);
                $this->fail('Jabatan tujuan setara atau lebih rendah harus ditolak.');
            } catch (DomainException $exception) {
                $this->assertSame('Jabatan tujuan harus lebih tinggi dari jabatan Pegawai saat ini.', $exception->getMessage());
            }
        }

        $this->assertDatabaseCount('career_goals', 0);
    }

    public function test_competency_assessment_is_audited_and_rejects_future_dates(): void
    {
        [$employee, , $hr, , $target] = $this->organization();
        $competency = Competency::create(['name' => 'Kepemimpinan']);
        PositionCompetency::create([
            'position_id' => $target->id, 'competency_id' => $competency->id, 'required_level' => 4,
        ]);
        $goal = CareerGoal::create(['user_id' => $employee->id, 'target_position_id' => $target->id]);

        $this->actingAs($hr);
        $assessment = EmployeeCompetency::create([
            'user_id' => $employee->id, 'competency_id' => $competency->id,
            'level' => 2, 'assessed_at' => today(), 'notes' => 'Asesmen awal',
        ]);
        $assessment->update(['level' => 3, 'notes' => 'Asesmen ulang']);

        $created = ActivityLog::where('action', 'competency.created')->sole();
        $updated = ActivityLog::where('action', 'competency.updated')->sole();
        $this->assertSame($hr->id, $created->user_id);
        $this->assertSame($hr->id, $updated->user_id);
        $this->assertEquals(2, $updated->data['changes']['level']['old']);
        $this->assertEquals(3, $updated->data['changes']['level']['new']);
        $this->assertSame(1, app(CareerGapService::class)->analyze($goal)->first()['gap']);

        try {
            EmployeeCompetency::create([
                'user_id' => $employee->id,
                'competency_id' => Competency::create(['name' => 'Komunikasi'])->id,
                'level' => 2,
                'assessed_at' => today()->addDay(),
            ]);
            $this->fail('Tanggal asesmen masa depan harus ditolak.');
        } catch (DomainException $exception) {
            $this->assertSame('Tanggal penilaian kompetensi tidak boleh di masa depan.', $exception->getMessage());
        }

        $assessment->delete();
        $this->assertSame($hr->id, ActivityLog::where('action', 'competency.deleted')->sole()->user_id);
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

    public function test_manager_recommendation_is_approved_without_hr_queue(): void
    {
        [$employee, $manager, $hr] = $this->organization();
        $training = Training::create(['name' => 'Komunikasi Lanjutan', 'type' => 'internal', 'is_active' => true]);
        $period = ReviewPeriod::create([
            'name' => 'Semester Rekomendasi', 'starts_at' => today()->startOfMonth(), 'ends_at' => today()->endOfMonth(),
            'kpi_weight' => 40, 'discipline_weight' => 20, 'manager_weight' => 20,
            'review_360_weight' => 20, 'base_bonus' => 1_000_000, 'is_active' => true,
        ]);
        $result = MeritResult::create([
            'review_period_id' => $period->id, 'employee_id' => $employee->id,
            'kpi_score' => 80, 'discipline_score' => 90, 'manager_score' => 75,
            'review_360_score' => 70, 'total_score' => 79, 'estimated_bonus' => 790_000,
        ]);

        $this->actingAs($manager);
        Filament::setCurrentPanel(Filament::getPanel('manager'));
        Livewire::test(ListMeritResults::class)
            ->assertTableActionHidden('recommend_training', $result);

        try {
            TrainingRequest::recommendByManager($manager, $employee, $training, $result, 'Belum final');
            $this->fail('Merit belum terpublikasi tidak boleh menjadi dasar rekomendasi.');
        } catch (DomainException $exception) {
            $this->assertSame('Rekomendasi pelatihan tidak valid.', $exception->getMessage());
        }

        $result->update(['published_at' => now()]);
        Livewire::test(ListMeritResults::class)
            ->assertTableActionVisible('recommend_training', $result)
            ->callTableAction('recommend_training', $result, [
                'training_id' => $training->id,
                'reason' => 'Dibutuhkan untuk penguatan komunikasi tim.',
            ])
            ->assertHasNoTableActionErrors();

        $request = TrainingRequest::sole();
        $this->assertSame(TrainingRequestStatus::Approved, $request->status);
        $this->assertNotNull($request->manager_decided_at);
        $this->assertNull($request->hr_verified_at);
        $this->assertTrue(TrainingRequest::visibleTo($employee)->whereKey($request)->exists());
        $this->assertFalse(TrainingRequest::where('status', TrainingRequestStatus::PendingHr)->exists());

        $log = ActivityLog::where('action', 'training.recommended')->sole();
        $this->assertSame($result->id, $log->data['merit_result_id']);
        $this->assertEquals(79, $log->data['total_score']);
        $this->assertFalse(ActivityLog::where('action', 'training.requested')->where('subject_id', $request->id)->exists());

        $this->actingAs($hr);
        $request->complete($hr, 'Lulus');
        $this->assertSame(TrainingRequestStatus::Completed, $request->fresh()->status);
    }

    public function test_manager_recommendation_rejects_invalid_or_duplicate_data(): void
    {
        [$employee, $manager] = $this->organization();
        $otherManager = User::factory()->create(['role' => UserRole::Manager]);
        $otherEmployee = User::factory()->create(['role' => UserRole::Employee, 'manager_id' => $otherManager->id]);
        $inactiveEmployee = User::factory()->create([
            'role' => UserRole::Employee, 'manager_id' => $manager->id, 'is_active' => false,
        ]);
        $training = Training::create(['name' => 'Aktif', 'type' => 'internal', 'is_active' => true]);
        $inactiveTraining = Training::create(['name' => 'Nonaktif', 'type' => 'internal', 'is_active' => false]);
        $period = ReviewPeriod::create([
            'name' => 'Validasi Rekomendasi', 'starts_at' => today(), 'ends_at' => today()->addMonth(),
            'kpi_weight' => 40, 'discipline_weight' => 20, 'manager_weight' => 20,
            'review_360_weight' => 20, 'base_bonus' => 0,
        ]);
        $result = MeritResult::create([
            'review_period_id' => $period->id, 'employee_id' => $employee->id,
            'kpi_score' => 0, 'discipline_score' => 100, 'manager_score' => 0,
            'review_360_score' => 0, 'total_score' => 20, 'estimated_bonus' => 0, 'published_at' => now(),
        ]);
        $inactiveResult = MeritResult::create([
            'review_period_id' => $period->id, 'employee_id' => $inactiveEmployee->id,
            'kpi_score' => 0, 'discipline_score' => 100, 'manager_score' => 0,
            'review_360_score' => 0, 'total_score' => 20, 'estimated_bonus' => 0, 'published_at' => now(),
        ]);

        foreach ([
            fn () => TrainingRequest::recommendByManager($manager, $otherEmployee, $training, $result, 'Bukan bawahan'),
            fn () => TrainingRequest::recommendByManager($manager, $inactiveEmployee, $training, $inactiveResult, 'Pegawai nonaktif'),
            fn () => TrainingRequest::recommendByManager($manager, $employee, $inactiveTraining, $result, 'Training nonaktif'),
        ] as $invalidRecommendation) {
            try {
                $invalidRecommendation();
                $this->fail('Rekomendasi tidak valid harus ditolak.');
            } catch (DomainException $exception) {
                $this->assertSame('Rekomendasi pelatihan tidak valid.', $exception->getMessage());
            }
        }

        TrainingRequest::recommendByManager($manager, $employee, $training, $result, 'Rekomendasi valid');

        try {
            TrainingRequest::recommendByManager($manager, $employee, $training, $result, 'Rekomendasi duplikat');
            $this->fail('Rekomendasi duplikat harus ditolak.');
        } catch (DomainException $exception) {
            $this->assertSame('Pelatihan ini sudah pernah diajukan atau direkomendasikan untuk pegawai tersebut.', $exception->getMessage());
        }

        $secondTraining = Training::create(['name' => 'Lain', 'type' => 'internal', 'is_active' => true]);
        try {
            TrainingRequest::create([
                'user_id' => $employee->id, 'training_id' => $secondTraining->id, 'manager_id' => $manager->id,
                'status' => TrainingRequestStatus::Approved, 'reason' => 'Lewati jalur domain', 'requested_at' => now(),
            ]);
            $this->fail('Status Approved langsung harus ditolak.');
        } catch (DomainException $exception) {
            $this->assertSame('Rekomendasi pelatihan Atasan harus dibuat melalui aksi rekomendasi.', $exception->getMessage());
        }

        $this->assertDatabaseCount('training_requests', 1);
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

    public function test_rejected_training_request_can_be_resubmitted(): void
    {
        [$employee, $manager] = $this->organization();
        $training = Training::create(['name' => 'Komunikasi', 'type' => 'internal', 'is_active' => true]);
        $request = TrainingRequest::create([
            'user_id' => $employee->id, 'training_id' => $training->id, 'manager_id' => $manager->id,
            'reason' => 'Alasan awal', 'requested_at' => now(),
        ]);
        $request->rejectByManager($manager, 'Perjelas manfaat');

        $request->resubmit($employee, 'Dibutuhkan untuk presentasi bulanan');

        $request->refresh();
        $this->assertSame(TrainingRequestStatus::PendingManager, $request->status);
        $this->assertSame('Dibutuhkan untuk presentasi bulanan', $request->reason);
        $this->assertNull($request->manager_notes);
        $this->assertDatabaseCount('training_requests', 1);
    }

    public function test_training_transition_reloads_status_before_writing(): void
    {
        [$employee, $manager] = $this->organization();
        $training = Training::create(['name' => 'Komunikasi', 'type' => 'internal', 'is_active' => true]);
        $request = TrainingRequest::create([
            'user_id' => $employee->id, 'training_id' => $training->id, 'manager_id' => $manager->id,
            'reason' => 'Perlu', 'requested_at' => now(),
        ]);
        $approval = $request->fresh();
        $staleRejection = $request->fresh();

        $approval->approveByManager($manager);

        try {
            $staleRejection->rejectByManager($manager, 'Ditolak terlambat');
            $this->fail('Transisi dari salinan status lama harus ditolak.');
        } catch (DomainException $exception) {
            $this->assertSame('Pengajuan pelatihan tidak dapat ditolak pengguna ini.', $exception->getMessage());
        }

        $this->assertSame(TrainingRequestStatus::PendingHr, $request->fresh()->status);
        $this->assertDatabaseCount('activity_logs', 2);
    }

    public function test_mentoring_dates_cannot_be_in_the_past(): void
    {
        [$employee, $manager] = $this->organization();
        $this->actingAs($employee);

        try {
            Mentoring::create([
                'employee_id' => $employee->id, 'manager_id' => $manager->id,
                'topic' => 'Karier', 'target' => 'Rencana promosi', 'requested_at' => now()->subMinute(),
            ]);
            $this->fail('Jadwal usulan mentoring lampau harus ditolak.');
        } catch (DomainException $exception) {
            $this->assertSame('Jadwal mentoring yang diajukan tidak boleh lampau.', $exception->getMessage());
        }

        $mentoring = Mentoring::create([
            'employee_id' => $employee->id, 'manager_id' => $manager->id,
            'topic' => 'Karier', 'target' => 'Rencana promosi', 'requested_at' => now()->addDay(),
        ]);
        $this->actingAs($manager);

        try {
            $mentoring->approve($manager, now()->subMinute());
            $this->fail('Jadwal persetujuan mentoring lampau harus ditolak.');
        } catch (DomainException $exception) {
            $this->assertSame('Jadwal mentoring yang disetujui tidak boleh lampau.', $exception->getMessage());
        }

        $this->assertSame(MentoringStatus::Pending, $mentoring->fresh()->status);
        $this->assertNull($mentoring->fresh()->scheduled_at);
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
        $current = Position::create(['unit_id' => $unit->id, 'name' => 'Staf', 'level' => 2]);
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
