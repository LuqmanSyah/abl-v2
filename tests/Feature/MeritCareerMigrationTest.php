<?php

namespace Tests\Feature;

use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Models\PerformanceReview;
use App\Models\Position;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MeritCareerMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_duplicates_are_cleaned_before_unique_indexes(): void
    {
        $this->seed();

        $employee = User::query()->where('role', UserRole::Employee)->firstOrFail();
        $skill = Skill::query()->firstOrFail();
        $userSkill = UserSkill::create([
            'user_id' => $employee->id,
            'skill_id' => $skill->id,
            'current_level' => 2,
        ]);
        $review = PerformanceReview::create([
            'user_id' => $employee->id,
            'reviewer_id' => $employee->manager_id,
            'period' => 'legacy',
            'start_date' => '2029-01-01',
            'end_date' => '2029-06-30',
            'status' => ReviewStatus::Draft,
        ]);
        $detail = $review->reviewKpiDetails->firstOrFail();
        $target = Position::query()->whereKeyNot($employee->position_id)->firstOrFail();
        $migration = require database_path(
            'migrations/2026_07_27_000200_enforce_merit_career_invariants.php',
        );

        $migration->down();

        $latestUserSkillId = DB::table('user_skills')->insertGetId([
            'user_id' => $employee->id,
            'skill_id' => $skill->id,
            'current_level' => 5,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('review_kpi_details')->insert([
            'performance_review_id' => $review->id,
            'kpi_id' => $detail->kpi_id,
            'self_score' => 70,
            'self_notes' => 'Catatan lama',
            'manager_score' => 99,
            'manager_notes' => 'Pertahankan catatan terisi',
            'weight' => $detail->weight,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('review_kpi_details')->insert([
            'performance_review_id' => $review->id,
            'kpi_id' => $detail->kpi_id,
            'self_notes' => 'Catatan terbaru',
            'manager_score' => 88,
            'weight' => $detail->weight,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $promotion = [
            'user_id' => $employee->id,
            'from_position_id' => $employee->position_id,
            'to_position_id' => $target->id,
            'proposed_by' => $employee->manager_id,
            'readiness_score' => 90,
            'effective_date' => today()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $promotionIds = [
            DB::table('promotions')->insertGetId([...$promotion, 'status' => 'proposed']),
            DB::table('promotions')->insertGetId([...$promotion, 'status' => 'approved_by_director']),
            DB::table('promotions')->insertGetId([...$promotion, 'status' => 'approved_by_director']),
            DB::table('promotions')->insertGetId([...$promotion, 'status' => 'approved_by_hr']),
        ];

        $migration->up();

        $this->assertDatabaseCount('user_skills', 1);
        $this->assertDatabaseHas('user_skills', [
            'id' => $latestUserSkillId,
            'current_level' => 5,
        ]);
        $this->assertSame(1, DB::table('review_kpi_details')
            ->where('performance_review_id', $review->id)
            ->where('kpi_id', $detail->kpi_id)
            ->count());
        $mergedDetail = DB::table('review_kpi_details')->find($detail->id);
        $this->assertNotNull($mergedDetail);
        $this->assertSame(70.0, (float) $mergedDetail->self_score);
        $this->assertSame('Catatan terbaru', $mergedDetail->self_notes);
        $this->assertSame(88.0, (float) $mergedDetail->manager_score);
        $this->assertSame('Pertahankan catatan terisi', $mergedDetail->manager_notes);
        $this->assertSame((float) $detail->weight, (float) $mergedDetail->weight);
        $this->assertEqualsWithDelta(
            88 * (float) $detail->weight / 100,
            (float) $mergedDetail->subtotal_score,
            0.01,
        );
        $this->assertDatabaseHas('promotions', [
            'id' => $promotionIds[2],
            'status' => 'approved_by_director',
            'active_lifecycle' => true,
        ]);
        $this->assertSame(3, DB::table('promotions')
            ->whereIn('id', $promotionIds)
            ->where('status', 'expired')
            ->whereNull('active_lifecycle')
            ->count());
        $this->assertDatabaseMissing('user_skills', ['id' => $userSkill->id]);
    }
}
