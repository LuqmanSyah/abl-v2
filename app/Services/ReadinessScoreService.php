<?php

namespace App\Services;

use App\Models\Position;
use App\Models\PositionSkill;
use App\Models\User;

class ReadinessScoreService
{
    public function calculate(User $user, Position $targetPosition): float
    {
        $requirements = $targetPosition->positionSkills()->get(['skill_id', 'min_required_level']);
        $requiredTotal = (int) $requirements->sum('min_required_level');

        if ($requiredTotal === 0) {
            return 0;
        }

        $levels = $user->userSkills()
            ->whereIn('skill_id', $requirements->pluck('skill_id'))
            ->pluck('current_level', 'skill_id');
        $currentTotal = $requirements->sum(
            fn (PositionSkill $requirement): int => min(
                (int) ($levels[$requirement->skill_id] ?? 0),
                $requirement->min_required_level,
            ),
        );

        return round($currentTotal / $requiredTotal * 100, 2);
    }
}
