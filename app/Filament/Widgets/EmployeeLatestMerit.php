<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MeritResults\MeritResultResource;
use App\Models\MeritResult;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmployeeLatestMerit extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $merit = MeritResult::where('employee_id', auth()->id())
            ->whereNotNull('published_at')
            ->latest('published_at')
            ->first();

        if (! $merit) {
            return [
                Stat::make('Merit Terakhir', 'Belum ada')
                    ->description('Hasil merit akan muncul setelah dipublikasi HR')
                    ->color('gray')
                    ->icon('heroicon-o-document-text'),
            ];
        }

        return [
            Stat::make('Skor KPI', number_format($merit->kpi_score, 2))
                ->description('Periode: ' . ($merit->reviewPeriod?->name ?? '-'))
                ->color('primary')
                ->icon('heroicon-o-chart-bar')
                ->url(MeritResultResource::getUrl('view', [$merit])),
            Stat::make('Skor Kedisiplinan', number_format($merit->discipline_score, 2))
                ->description('Berdasarkan status absensi')
                ->color('info')
                ->icon('heroicon-o-check-circle'),
            Stat::make('Total Skor', number_format($merit->total_score, 2))
                ->description('Estimasi bonus: Rp ' . number_format($merit->estimated_bonus ?? 0, 0, ',', '.'))
                ->color('success')
                ->icon('heroicon-o-trophy')
                ->url(MeritResultResource::getUrl('view', [$merit])),
        ];
    }
}
