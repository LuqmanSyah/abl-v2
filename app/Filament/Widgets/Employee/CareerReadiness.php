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
        $paths = CareerPath::query()
            ->where('current_position_id', $user->position_id)
            ->with('nextPosition')
            ->orderBy('next_position_id')
            ->get();

        if ($paths->isEmpty()) {
            return [
                Stat::make('Career Readiness', '-')
                    ->description('Jalur karir belum tersedia')
                    ->color('gray'),
            ];
        }

        return $paths
            ->map(function (CareerPath $path) use ($user): Stat {
                $score = app(ReadinessScoreService::class)->calculate($user, $path->nextPosition);

                return Stat::make("Career Readiness: {$path->nextPosition->title}", "{$score}%")
                    ->description("Target: {$path->nextPosition->title}")
                    ->color($score >= 80 ? 'success' : 'warning');
            })
            ->all();
    }
}
