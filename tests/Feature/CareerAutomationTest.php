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
            'grade' => 'C',
            'status' => ReviewStatus::Approved,
        ]);

        $this->assertSame(80.0, app(ReadinessScoreService::class)->calculate($employee, $path->nextPosition));

        $employee->update(['join_date' => now()->subMonths($path->min_experience_months - 1)]);
        $review->update(['grade' => 'B']);
        $this->artisan('career:scan-candidates')->assertSuccessful();
        $this->assertDatabaseCount(Promotion::class, 0);

        $employee->update(['join_date' => now()->subMonths($path->min_experience_months + 1)]);
        $review->update(['grade' => 'C']);
        $this->artisan('career:scan-candidates')->assertSuccessful();
        $this->assertDatabaseCount(Promotion::class, 0);

        $review->update(['grade' => 'B']);
        $this->artisan('career:scan-candidates')->assertSuccessful();
        $this->artisan('career:scan-candidates')->assertSuccessful();

        $promotion = Promotion::sole();
        $this->assertSame(PromotionStatus::Proposed, $promotion->status);
        $this->assertSame($employee->manager_id, $promotion->proposed_by);
        $this->assertSame(80.0, (float) $promotion->readiness_score);

        $approved = Promotion::create([
            'user_id' => $employee->id,
            'from_position_id' => $path->current_position_id,
            'to_position_id' => $path->next_position_id,
            'proposed_by' => $employee->manager_id,
            'readiness_score' => 80,
            'status' => PromotionStatus::ApprovedByHr,
        ]);
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

        Livewire::test(ListPromotions::class)
            ->callAction('create', data: [
                'user_id' => $employee->id,
                'from_position_id' => $from->id,
                'to_position_id' => $to->id,
                'proposed_by' => $admin->id,
                'readiness_score' => 88,
                'status' => PromotionStatus::Proposed->value,
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
        $this->assertDatabaseHas(Promotion::class, [
            'user_id' => $employee->id,
            'readiness_score' => 88,
        ]);
        Livewire::test(ListPromotions::class)
            ->assertActionExists(TestAction::make('edit')->table(Promotion::first()));
    }

    public function test_automation_commands_are_scheduled(): void
    {
        $events = collect(app(Schedule::class)->events());
        $expire = $events->first(fn ($event) => str_contains($event->command, 'career:expire-promotions'));
        $scan = $events->first(fn ($event) => str_contains($event->command, 'career:scan-candidates'));

        $this->assertNotNull($expire);
        $this->assertSame('15 0 * * *', $expire->expression);
        $this->assertSame('Asia/Jakarta', $expire->timezone);
        $this->assertNotNull($scan);
        $this->assertSame('30 0 1 * *', $scan->expression);
        $this->assertSame('Asia/Jakarta', $scan->timezone);
    }
}
