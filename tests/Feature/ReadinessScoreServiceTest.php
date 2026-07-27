<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\BranchOffice;
use App\Models\Department;
use App\Models\Position;
use App\Models\PositionSkill;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;
use App\Models\WorkSchedule;
use App\Services\ReadinessScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadinessScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_score_caps_levels_and_handles_position_without_requirements(): void
    {
        $department = Department::create(['name' => 'Technology', 'code' => 'TECH']);
        $currentPosition = Position::create([
            'department_id' => $department->id,
            'title' => 'Engineer',
            'level' => 1,
        ]);
        $targetPosition = Position::create([
            'department_id' => $department->id,
            'title' => 'Senior Engineer',
            'level' => 2,
        ]);
        $emptyPosition = Position::create([
            'department_id' => $department->id,
            'title' => 'Specialist',
            'level' => 2,
        ]);
        $skill = Skill::create(['name' => 'Laravel', 'category' => 'Technical']);
        $schedule = WorkSchedule::create([
            'name' => 'Regular',
            'check_in_time' => '08:00',
            'check_out_time' => '17:00',
            'late_tolerance_minutes' => 15,
            'alfa_cutoff_minutes' => 120,
        ]);
        $branch = BranchOffice::create([
            'name' => 'Jakarta',
            'code' => 'JKT',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'allowed_radius_meters' => 100,
        ]);
        $manager = User::factory()->create([
            'position_id' => $currentPosition->id,
            'work_schedule_id' => $schedule->id,
            'branch_office_id' => $branch->id,
            'role' => UserRole::Manager,
        ]);
        $user = User::factory()->create([
            'position_id' => $currentPosition->id,
            'work_schedule_id' => $schedule->id,
            'branch_office_id' => $branch->id,
            'manager_id' => $manager->id,
        ]);

        PositionSkill::create([
            'position_id' => $targetPosition->id,
            'skill_id' => $skill->id,
            'min_required_level' => 3,
        ]);
        UserSkill::create([
            'user_id' => $user->id,
            'skill_id' => $skill->id,
            'current_level' => 5,
        ]);

        $service = app(ReadinessScoreService::class);

        $this->assertSame(100.0, $service->calculate($user, $targetPosition));
        $this->assertSame(0.0, $service->calculate($user, $emptyPosition));
    }
}
