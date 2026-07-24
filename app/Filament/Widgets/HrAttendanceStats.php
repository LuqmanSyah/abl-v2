<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\Attendance;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HrAttendanceStats extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $sixMonthsAgo = now()->subMonths(6)->startOfMonth();

        $monthlyCount = Attendance::whereBetween('captured_at', [$thisMonth, now()])->count();
        $prevMonthlyCount = Attendance::whereBetween('captured_at', [$lastMonth, $thisMonth])->count();

        $dailyAvg = $monthlyCount / max(now()->diffInDays($thisMonth), 1);
        $prevDailyAvg = $prevMonthlyCount / max($thisMonth->diffInDays($lastMonth), 1);

        $change = $prevDailyAvg > 0 ? round((($dailyAvg - $prevDailyAvg) / $prevDailyAvg) * 100, 1) : 0;

        $total6Months = Attendance::where('captured_at', '>=', $sixMonthsAgo)->count();
        $monthCount = max(now()->diffInMonths($sixMonthsAgo), 1);
        $avg6Months = round($total6Months / $monthCount);

        return [
            Stat::make('Absensi Bulan Ini', number_format($monthlyCount))
                ->description(($change >= 0 ? 'Naik ' : 'Turun ') . abs($change) . '% dari bulan lalu')
                ->color($change >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-calendar-days')
                ->url(AttendanceResource::getUrl('index')),
            Stat::make('Absensi Hari Ini', number_format(Attendance::whereDate('captured_at', today())->count()))
                ->description(now()->isoFormat('dddd, D MMM Y'))
                ->color('info')
                ->icon('heroicon-o-map-pin')
                ->url(AttendanceResource::getUrl('index')),
            Stat::make('Rata-rata 6 Bulan', number_format($avg6Months) . '/bulan')
                ->description('Total ' . number_format($total6Months) . ' sesi')
                ->color('primary')
                ->icon('heroicon-o-clock')
                ->url(AttendanceResource::getUrl('index')),
        ];
    }
}
