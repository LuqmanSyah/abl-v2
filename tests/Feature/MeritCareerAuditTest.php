<?php

namespace Tests\Feature;

use App\Enums\PromotionStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Exceptions\BusinessRuleException;
use App\Filament\Resources\PerformanceReviewResource;
use App\Filament\Resources\UserSkillResource\Pages\ListUserSkills;
use App\Models\Kpi;
use App\Models\PerformanceReview;
use App\Models\Position;
use App\Models\Promotion;
use App\Models\ReviewKpiDetail;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;
use App\Notifications\PromotionApproved;
use App\Notifications\PromotionAwaitingDirectorApproval;
use App\Notifications\PromotionRejected;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class MeritCareerAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_review_state_and_ownership_follow_current_manager_until_locked(): void
    {
        $employee = User::query()->where('role', UserRole::Employee)->firstOrFail();
        $firstManager = $employee->manager;
        $nextManager = User::query()
            ->where('role', UserRole::Manager)
            ->whereKeyNot($firstManager)
            ->firstOrFail();
        $hr = User::query()->where('role', UserRole::HrAdmin)->firstOrFail();
        $director = User::query()->where('role', UserRole::Director)->firstOrFail();

        $this->assertBusinessRule(
            fn () => PerformanceReview::create([
                'user_id' => $employee->id,
                'reviewer_id' => $firstManager->id,
                'period' => 'illegal',
                'start_date' => '2028-01-01',
                'end_date' => '2028-06-30',
                'status' => ReviewStatus::Approved,
            ]),
            'Review baru wajib dimulai dari status draft.',
        );

        $review = PerformanceReview::create([
            'user_id' => $employee->id,
            'reviewer_id' => $nextManager->id,
            'period' => '2028-H1',
            'start_date' => '2028-01-01',
            'end_date' => '2028-06-30',
            'status' => ReviewStatus::Draft,
        ]);
        $draft = PerformanceReview::create([
            'user_id' => $employee->id,
            'reviewer_id' => $firstManager->id,
            'period' => '2028-H2',
            'start_date' => '2028-07-01',
            'end_date' => '2028-12-31',
            'status' => ReviewStatus::Draft,
        ]);

        $this->assertSame($firstManager->id, $review->reviewer_id);
        $this->actingAs($firstManager);
        $this->assertTrue(PerformanceReviewResource::canEdit($review));

        $employee->update(['manager_id' => $nextManager->id]);

        $this->assertFalse(PerformanceReviewResource::canEdit($review));
        $this->actingAs($nextManager);
        $this->assertTrue(PerformanceReviewResource::canEdit($review));
        $review->update(['period' => '2028-H1 transfer']);
        $this->assertSame($nextManager->id, $review->fresh()->reviewer_id);

        $this->actingAs($hr);
        $this->assertFalse(PerformanceReviewResource::canEdit($review));
        $this->assertFalse(PerformanceReviewResource::canCreate());

        $this->assertBusinessRule(
            fn () => $draft->update(['status' => ReviewStatus::Approved]),
            'Transisi review dari draft ke approved tidak diizinkan.',
        );

        $review->reviewKpiDetails->each->update(['manager_score' => 80]);
        $review->submit($nextManager);
        $review->approve($hr);
        $review->lock($director);

        $employee->update(['manager_id' => $firstManager->id]);
        $this->assertBusinessRule(
            fn () => $review->update(['period' => '2028-H1 locked']),
            'Rapor terkunci tidak dapat diubah.',
        );
        $this->assertBusinessRule(
            fn () => $review->reviewKpiDetails()->firstOrFail()->update(['manager_score' => 90]),
            'Detail KPI hanya dapat diubah saat review berstatus draft.',
        );

        $this->assertSame($nextManager->id, $review->fresh()->reviewer_id);
        $this->assertTrue(PerformanceReview::query()->published()->whereKey($review)->exists());
        $this->assertFalse(PerformanceReview::query()->published()->whereKey($draft)->exists());
    }

    public function test_review_workflow_rechecks_actor_and_locked_state_and_requires_published_scores(): void
    {
        Notification::fake();

        $employee = User::query()->where('role', UserRole::Employee)->firstOrFail();
        $firstManager = $employee->manager;
        $nextManager = User::query()
            ->where('role', UserRole::Manager)
            ->whereKeyNot($firstManager)
            ->firstOrFail();
        $hr = User::query()->where('role', UserRole::HrAdmin)->firstOrFail();
        $director = User::query()->where('role', UserRole::Director)->firstOrFail();
        $review = PerformanceReview::create([
            'user_id' => $employee->id,
            'reviewer_id' => $firstManager->id,
            'period' => '2030-H1',
            'start_date' => '2030-01-01',
            'end_date' => '2030-06-30',
            'status' => ReviewStatus::Draft,
        ]);
        $review->reviewKpiDetails->each->update(['manager_score' => 80]);

        $this->assertBusinessRule(
            fn () => $review->update(['status' => ReviewStatus::Submitted]),
            'Status review hanya dapat diubah melalui aksi workflow.',
        );
        $review->refresh();
        $staleReview = $review->fresh();
        $employee->update(['manager_id' => $nextManager->id]);

        $this->assertBusinessRule(
            fn () => $review->submit($firstManager),
            'Hanya Manager langsung aktif yang dapat submit review draft.',
        );

        $review->submit($nextManager);
        $this->assertBusinessRule(
            fn () => $staleReview->submit($nextManager),
            'Hanya Manager langsung aktif yang dapat submit review draft.',
        );

        $this->assertBusinessRule(
            fn () => $review->update(['status' => ReviewStatus::Approved]),
            'Review published wajib memiliki skor merit dan grade lengkap.',
        );
        $this->assertSame(ReviewStatus::Submitted, $review->fresh()->status);
        $review->refresh();

        $this->assertBusinessRule(
            fn () => $review->approve($nextManager),
            'Hanya HR aktif yang dapat menyetujui review submitted.',
        );

        $staleHr = $hr->fresh();
        DB::table('users')->where('id', $hr->id)->update(['status' => false]);
        $this->assertBusinessRule(
            fn () => $review->approve($staleHr),
            'Hanya HR aktif yang dapat menyetujui review submitted.',
        );
        DB::table('users')->where('id', $hr->id)->update(['status' => true]);

        $review->approve($staleHr);
        $published = $review->fresh();
        $this->assertNotNull($published->attendance_score);
        $this->assertNotNull($published->manager_kpi_score);
        $this->assertNotNull($published->final_merit_score);
        $this->assertNotNull($published->grade);

        $this->assertBusinessRule(
            fn () => $review->lock($hr),
            'Hanya Director aktif yang dapat mengunci review approved.',
        );
        $review->lock($director);
        $this->assertSame(ReviewStatus::Locked, $review->fresh()->status);
    }

    public function test_promotion_state_duplicate_guard_application_and_notifications(): void
    {
        Notification::fake();

        $employee = User::query()->where('role', UserRole::Employee)->firstOrFail();
        $manager = $employee->manager;
        $director = User::query()->where('role', UserRole::Director)->firstOrFail();
        $target = Position::query()->whereKeyNot($employee->position_id)->firstOrFail();

        $this->assertBusinessRule(
            fn () => Promotion::create([
                ...$this->promotionData($employee, $target),
                'status' => PromotionStatus::ApprovedByDirector,
                'effective_date' => today(),
            ]),
            'Promosi baru wajib dimulai dari status proposed.',
        );

        $promotion = Promotion::create($this->promotionData($employee, $target));

        $this->assertBusinessRule(
            fn () => Promotion::create($this->promotionData($employee, $target)),
            'Proposal aktif untuk karyawan dan posisi tujuan tersebut sudah ada.',
        );
        $this->assertBusinessRule(
            fn () => $promotion->update(['status' => PromotionStatus::ApprovedByDirector]),
            'Transisi promosi dari proposed ke approved_by_director tidak diizinkan.',
        );

        $stalePromotion = $promotion->fresh();
        $promotion->refresh()->transitionTo(PromotionStatus::ApprovedByHr);
        $stalePromotion->transitionTo(PromotionStatus::ApprovedByHr);
        Notification::assertSentTo($director, PromotionAwaitingDirectorApproval::class);
        Notification::assertSentToTimes($director, PromotionAwaitingDirectorApproval::class, 1);

        $this->assertBusinessRule(
            fn () => $promotion->transitionTo(PromotionStatus::ApprovedByDirector),
            'Tanggal efektif wajib diisi sebelum persetujuan Director.',
        );

        $promotion->refresh()->transitionTo(
            PromotionStatus::ApprovedByDirector,
            ['effective_date' => today()],
        );

        $this->assertSame($target->id, $employee->fresh()->position_id);
        $this->assertNotNull($promotion->fresh()->applied_at);
        $this->assertNull($promotion->fresh()->active_lifecycle);
        Notification::assertSentTo($employee, PromotionApproved::class);
        Notification::assertSentTo($manager, PromotionApproved::class);

        $rejectedTarget = Position::query()
            ->whereNotIn('id', [$employee->fresh()->position_id, $target->id])
            ->firstOrFail();
        $rejected = Promotion::create($this->promotionData($employee->fresh(), $rejectedTarget));
        $rejected->transitionTo(PromotionStatus::Rejected);

        Notification::assertSentTo($employee, PromotionRejected::class);
        Notification::assertSentTo($manager, PromotionRejected::class);
        $notification = new PromotionRejected($rejected);
        $this->assertSame(url('/app'), $notification->toDatabase($employee)['url']);
        $this->assertSame(url('/admin/promotions'), $notification->toDatabase($manager)['url']);
    }

    public function test_future_promotion_is_applied_once_on_effective_date(): void
    {
        Notification::fake();

        $employee = User::query()->where('role', UserRole::Employee)->firstOrFail();
        $target = Position::query()->whereKeyNot($employee->position_id)->firstOrFail();
        $effectiveDate = today()->addDay();
        $promotion = Promotion::create($this->promotionData($employee, $target));

        $promotion->transitionTo(PromotionStatus::ApprovedByHr);
        $promotion->transitionTo(
            PromotionStatus::ApprovedByDirector,
            ['effective_date' => $effectiveDate],
        );

        $this->assertNotSame($target->id, $employee->fresh()->position_id);
        $this->assertNull($promotion->fresh()->applied_at);

        $this->travelTo($effectiveDate->startOfDay());
        $this->artisan('career:apply-promotions')->assertSuccessful();
        $appliedAt = $promotion->fresh()->applied_at;
        $this->artisan('career:apply-promotions')->assertSuccessful();

        $this->assertSame($target->id, $employee->fresh()->position_id);
        $this->assertTrue($promotion->fresh()->applied_at->equalTo($appliedAt));
    }

    public function test_due_promotion_does_not_overwrite_a_newer_position_change(): void
    {
        Notification::fake();

        $employee = User::query()->where('role', UserRole::Employee)->firstOrFail();
        $originalPositionId = $employee->position_id;
        $target = Position::query()->whereKeyNot($originalPositionId)->firstOrFail();
        $newerPosition = Position::query()
            ->whereNotIn('id', [$originalPositionId, $target->id])
            ->firstOrFail();
        $effectiveDate = today()->addDay();
        $promotion = Promotion::create($this->promotionData($employee, $target));

        $promotion->transitionTo(PromotionStatus::ApprovedByHr);
        $promotion->transitionTo(
            PromotionStatus::ApprovedByDirector,
            ['effective_date' => $effectiveDate],
        );
        $employee->update(['position_id' => $newerPosition->id]);

        $this->travelTo($effectiveDate->startOfDay());

        $this->assertFalse($promotion->applyIfDue());
        $this->artisan('career:apply-promotions')->assertSuccessful();
        $this->assertSame($newerPosition->id, $employee->fresh()->position_id);
        $this->assertSame(PromotionStatus::Expired, $promotion->fresh()->status);
        $this->assertNull($promotion->fresh()->active_lifecycle);
        $this->assertNull($promotion->fresh()->applied_at);

        $replacement = Promotion::create($this->promotionData($employee->fresh(), $target));
        $this->assertSame(PromotionStatus::Proposed, $replacement->status);
    }

    public function test_database_constraints_reject_duplicate_snapshots_and_user_skills(): void
    {
        $employee = User::query()->where('role', UserRole::Employee)->firstOrFail();
        $review = PerformanceReview::create([
            'user_id' => $employee->id,
            'reviewer_id' => $employee->manager_id,
            'period' => '2029-H1',
            'start_date' => '2029-01-01',
            'end_date' => '2029-06-30',
        ]);
        $detail = $review->reviewKpiDetails->firstOrFail();

        $this->assertQueryFails(fn () => ReviewKpiDetail::create([
            'performance_review_id' => $review->id,
            'kpi_id' => $detail->kpi_id,
            'weight' => $detail->weight,
        ]));

        $skill = $employee->userSkills()->first()?->skill
            ?? Skill::query()->firstOrFail();
        UserSkill::create([
            'user_id' => $employee->id,
            'skill_id' => $skill->id,
            'current_level' => 3,
        ]);

        $this->actingAs(User::query()->where('role', UserRole::HrAdmin)->firstOrFail());
        Livewire::test(ListUserSkills::class)
            ->callAction('create', data: [
                'user_id' => $employee->id,
                'skill_id' => $skill->id,
                'current_level' => 5,
            ])
            ->assertHasActionErrors(['skill_id']);

        $this->assertQueryFails(fn () => UserSkill::create([
            'user_id' => $employee->id,
            'skill_id' => $skill->id,
            'current_level' => 5,
        ]));

        $this->assertBusinessRule(
            fn () => Kpi::create(['name' => 'Invalid', 'category' => 'Audit', 'weight' => 1]),
            'Total bobot master KPI harus tetap 100.',
        );
    }

    public function test_kpi_rebalance_updates_all_weights_atomically(): void
    {
        $kpis = Kpi::query()->orderBy('id')->get();
        $weights = $kpis->mapWithKeys(fn (Kpi $kpi, int $index): array => [
            $kpi->id => $index === 0 ? 100 : 0,
        ])->all();

        Kpi::rebalance($weights);

        $this->assertSame(100.0, (float) $kpis->first()->fresh()->weight);
        $this->assertSame(100.0, (float) Kpi::query()->sum('weight'));

        $weights[$kpis->first()->id] = 90;
        $this->assertBusinessRule(
            fn () => Kpi::rebalance($weights),
            'Total bobot master KPI harus tetap 100.',
        );
        $this->assertSame(100.0, (float) $kpis->first()->fresh()->weight);
    }

    /** @return array<string, mixed> */
    private function promotionData(User $employee, Position $target): array
    {
        return [
            'user_id' => $employee->id,
            'from_position_id' => $employee->position_id,
            'to_position_id' => $target->id,
            'proposed_by' => $employee->manager_id,
            'readiness_score' => 90,
            'status' => PromotionStatus::Proposed,
        ];
    }

    private function assertBusinessRule(callable $callback, string $message): void
    {
        try {
            $callback();
            $this->fail('Business rule seharusnya menolak operasi.');
        } catch (BusinessRuleException $exception) {
            $this->assertSame($message, $exception->getMessage());
        }
    }

    private function assertQueryFails(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Unique constraint seharusnya menolak duplicate.');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }
    }
}
