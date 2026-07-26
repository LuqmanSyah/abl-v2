<?php

namespace App\Filament\Widgets\Admin;

use App\Enums\UserRole;
use App\Models\PerformanceReview;
use Filament\Widgets\ChartWidget;

class MeritDistribution extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Distribusi Grade Merit';

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::Director;
    }

    protected function getData(): array
    {
        $counts = PerformanceReview::query()
            ->whereNotNull('grade')
            ->selectRaw('grade, COUNT(*) AS aggregate')
            ->groupBy('grade')
            ->pluck('aggregate', 'grade');
        $grades = ['A', 'B', 'C', 'D'];

        return [
            'datasets' => [[
                'label' => 'Karyawan',
                'data' => array_map(fn (string $grade): int => (int) ($counts[$grade] ?? 0), $grades),
                'backgroundColor' => ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444'],
            ]],
            'labels' => $grades,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
