<?php

namespace App\Filament\Widgets\Employee;

use App\Models\PerformanceReview;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LatestMeritGrade extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $review = PerformanceReview::query()
            ->where('user_id', auth()->id())
            ->whereNotNull('grade')
            ->latest('end_date')
            ->first();

        return [
            Stat::make('Merit Terakhir', $review?->grade ?? '-')
                ->description($review
                    ? "Skor {$review->final_merit_score} · {$review->period}"
                    : 'Belum ada hasil merit')
                ->color(match ($review?->grade) {
                    'A' => 'success',
                    'B' => 'info',
                    'C' => 'warning',
                    'D' => 'danger',
                    default => 'gray',
                }),
        ];
    }
}
