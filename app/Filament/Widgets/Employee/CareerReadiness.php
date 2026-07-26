<?php

namespace App\Filament\Widgets\Employee;

use App\Models\CareerPath;
use App\Models\User;
use App\Services\ReadinessScoreService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CareerReadiness extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        /** @var User $user */
        $user = auth()->user();
        // ponytail: first configured path; add user-selectable target when multiple paths are supported.
        $path = CareerPath::query()
            ->where('current_position_id', $user->position_id)
            ->with('nextPosition')
            ->first();
        $score = $path
            ? app(ReadinessScoreService::class)->calculate($user, $path->nextPosition)
            : null;

        return [
            Stat::make('Career Readiness', $score === null ? '-' : "{$score}%")
                ->description($path ? "Target: {$path->nextPosition->title}" : 'Jalur karir belum tersedia')
                ->color($score === null ? 'gray' : ($score >= 80 ? 'success' : 'warning')),
        ];
    }
}
