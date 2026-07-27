<?php

namespace Tests\Feature;

use App\Enums\IdpStatus;
use App\Enums\PromotionStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Filament\Resources\CareerPathResource\Pages\ListCareerPaths;
use App\Filament\Resources\IndividualDevelopmentPlanResource\Pages\ListIndividualDevelopmentPlans;
use App\Filament\Resources\PromotionResource\Pages\ListPromotions;
use App\Models\CareerPath;
use App\Models\IndividualDevelopmentPlan;
use App\Models\PerformanceReview;
use App\Models\Position;
use App\Models\Promotion;
use App\Models\User;
use App\Models\UserSkill;
use App\Services\ReadinessScoreService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CareerAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_scan_applies_all_criteria_and_recycles_expired_proposals(): void
    {
        $this->seed();

        $employee = User::query()->where('email', 'rina.marlina@company.com')->firstOrFail();
        $path = CareerPath::query()
            ->where('current_position_id', $employee->position_id)
            ->with('nextPosition.positionSkills')
            ->firstOrFail();
        $requirements = $path->nextPosition->positionSkills;

        UserSkill::create([
            'user_id' => $employee->id,
            'skill_id' => $requirements[0]->skill_id,
            'current_level' => 5,
        ]);
        UserSkill::create([
            'user_id' => $employee->id,
            'skill_id' => $requirements[1]->skill_id,
            'current_level' => 1,
        ]);
        $review = PerformanceReview::create([
            'user_id' => $employee->id,
            'reviewer_id' => $employee->manager_id,
            'period' => '2026-H1',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'status' => ReviewStatus::Draft,
        ]);
        $review->reviewKpiDetails->each->update(['manager_score' => 80]);
        $review->submit($employee->manager);
        $review->approve(User::query()->where('role', UserRole::HrAdmin)->firstOrFail());
        $review->update(['grade' => 'C']);

        $this->assertSame(80.0, app(ReadinessScoreService::class)->calculate($employee, $path->nextPosition));

        $employee->update(['join_date' => now()->subMonths($path->min_experience_months - 1)]);
        $review->update(['grade' => 'B']);
        $this->artisan('career:scan-candidates')->assertSuccessful();
        $this->assertDatabaseCount(Promotion::class, 0);

        $employee->update(['join_date' => now()->subMonths($path->min_experience_months + 1)]);
        $review->update(['grade' => 'C']);
        PerformanceReview::create([
            'user_id' => $employee->id,
            'reviewer_id' => $employee->manager_id,
            'period' => '2026-H2 draft',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'grade' => 'A',
            'status' => ReviewStatus::Draft,
        ]);
        $this->artisan('career:scan-candidates')->assertSuccessful();
        $this->assertDatabaseCount(Promotion::class, 0);

        $review->update(['grade' => 'B']);
        $raceInserted = false;
        Promotion::creating(function (Promotion $creating) use (&$raceInserted): void {
            if ($raceInserted) {
                return;
            }

            $raceInserted = true;
            DB::table('promotions')->insert([
                ...$creating->getAttributes(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
        $this->artisan('career:scan-candidates')->assertSuccessful();
        $this->artisan('career:scan-candidates')->assertSuccessful();

        $promotion = Promotion::sole();
        $this->assertSame(PromotionStatus::Proposed, $promotion->status);
        $this->assertSame($employee->manager_id, $promotion->proposed_by);
        $this->assertSame(80.0, (float) $promotion->readiness_score);

        $otherTarget = Position::query()
            ->whereKeyNot($path->current_position_id)
            ->whereKeyNot($path->next_position_id)
            ->firstOrFail();
        $approved = Promotion::create([
            'user_id' => $employee->id,
            'from_position_id' => $path->current_position_id,
            'to_position_id' => $otherTarget->id,
            'proposed_by' => $employee->manager_id,
            'readiness_score' => 80,
            'status' => PromotionStatus::Proposed,
        ]);
        $approved->transitionTo(PromotionStatus::ApprovedByHr);
        $promotion->forceFill(['created_at' => now()->subDays(31)])->saveQuietly();
        $approved->forceFill(['created_at' => now()->subDays(31)])->saveQuietly();

        $this->artisan('career:expire-promotions')->assertSuccessful();

        $this->assertSame(PromotionStatus::Expired, $promotion->fresh()->status);
        $this->assertSame(PromotionStatus::ApprovedByHr, $approved->fresh()->status);

        $approved->delete();
        $this->artisan('career:scan-candidates')->assertSuccessful();

        $this->assertCount(1, Promotion::candidatePool()->get());
        $this->assertDatabaseCount(Promotion::class, 2);
    }

    public function test_career_resources_create_records(): void
    {
        $this->seed();

        $admin = User::query()->where('role', UserRole::HrAdmin)->firstOrFail();
        $employee = User::query()->where('role', UserRole::Employee)->firstOrFail();
        $from = Position::query()->where('title', 'Akuntan')->firstOrFail();
        $to = Position::query()->where('title', 'Finance Manager')->firstOrFail();
        $employee->update(['position_id' => $from->id]);
        $manager = $employee->manager;
        $this->actingAs($admin);

        Livewire::test(ListCareerPaths::class)
            ->callAction('create', data: [
                'current_position_id' => $from->id,
                'next_position_id' => $to->id,
                'min_experience_months' => 24,
                'min_merit_grade' => 'B',
            ])
            ->assertHasNoFormErrors();

        Livewire::test(ListIndividualDevelopmentPlans::class)
            ->callAction('create', data: [
                'user_id' => $employee->id,
                'mentor_id' => $employee->manager_id,
                'title' => 'Pengembangan Kepemimpinan',
                'action_plan' => 'Memimpin satu proyek lintas tim.',
                'progress_percentage' => 10,
                'target_completion_date' => '2027-06-30',
                'status' => IdpStatus::Active->value,
            ])
            ->assertHasNoFormErrors();

        $this->actingAs($manager);
        Livewire::test(ListPromotions::class)
            ->callAction('create', data: [
                'user_id' => $employee->id,
                'to_position_id' => $to->id,
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(CareerPath::class, [
            'current_position_id' => $from->id,
            'next_position_id' => $to->id,
        ]);
        $this->assertDatabaseHas(IndividualDevelopmentPlan::class, [
            'user_id' => $employee->id,
            'progress_percentage' => 10,
        ]);
        $promotion = Promotion::query()->where('user_id', $employee->id)->latest('id')->firstOrFail();
        $this->assertSame($from->id, $promotion->from_position_id);
        $this->assertSame($manager->id, $promotion->proposed_by);
        $this->assertSame(
            app(ReadinessScoreService::class)->calculate($employee, $to),
            (float) $promotion->readiness_score,
        );

        $this->actingAs($admin);
        Livewire::test(ListPromotions::class)
            ->callAction(TestAction::make('approve_hr')->table($promotion));
        $this->assertSame(PromotionStatus::ApprovedByHr, $promotion->fresh()->status);

        $director = User::query()->where('role', UserRole::Director)->firstOrFail();
        $this->actingAs($director);
        Livewire::test(ListPromotions::class)
            ->callAction(
                TestAction::make('approve_director')->table($promotion),
                data: ['effective_date' => '2027-01-01'],
            );
        $this->assertSame(PromotionStatus::ApprovedByDirector, $promotion->fresh()->status);
    }

    public function test_automation_commands_are_scheduled(): void
    {
        $events = collect(app(Schedule::class)->events());
        $expire = $events->first(fn ($event) => str_contains($event->command, 'career:expire-promotions'));
        $apply = $events->first(fn ($event) => str_contains($event->command, 'career:apply-promotions'));
        $scan = $events->first(fn ($event) => str_contains($event->command, 'career:scan-candidates'));

        $this->assertNotNull($expire);
        $this->assertSame('15 0 * * *', $expire->expression);
        $this->assertSame('Asia/Jakarta', $expire->timezone);
        $this->assertNotNull($apply);
        $this->assertSame('20 0 * * *', $apply->expression);
        $this->assertSame('Asia/Jakarta', $apply->timezone);
        $this->assertNotNull($scan);
        $this->assertSame('30 0 1 * *', $scan->expression);
        $this->assertSame('Asia/Jakarta', $scan->timezone);
    }
}
