<?php

namespace App\Filament\Widgets\Admin;

use App\Enums\DailySummaryStatus;
use App\Enums\UserRole;
use App\Models\DailyAttendanceSummary;
use App\Models\User;
use Carbon\CarbonInterface;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class AttendanceDropAlert extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->role === UserRole::HrAdmin;
    }

    protected function getStats(): array
    {
        $current = $this->rate(now()->startOfMonth(), now());
        $previous = $this->rate(now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth());
        $change = round($current - $previous, 1);

        return [
            Stat::make('Tingkat Kehadiran Bulan Ini', "{$current}%")
                ->description($change < 0
                    ? 'Turun '.abs($change).'% dari bulan lalu'
                    : 'Tidak turun dari bulan lalu')
                ->descriptionIcon($change < 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-arrow-trending-up')
                ->color($change < 0 ? 'danger' : 'success'),
        ];
    }

    private function rate(CarbonInterface $start, CarbonInterface $end): float
    {
        $tracked = [
            DailySummaryStatus::Present,
            DailySummaryStatus::Late,
            DailySummaryStatus::Alfa,
            DailySummaryStatus::MissingCheckout,
        ];
        $query = DailyAttendanceSummary::query()
            ->whereBetween('date', [$start, $end])
            ->whereIn('status', $tracked);
        $total = (clone $query)->count();

        return $total
            ? round((clone $query)->whereIn('status', [
                DailySummaryStatus::Present,
                DailySummaryStatus::Late,
            ])->count() / $total * 100, 1)
            : 0;
    }
}
