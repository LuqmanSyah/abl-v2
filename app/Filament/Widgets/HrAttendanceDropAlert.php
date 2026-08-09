<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HrAttendanceDropAlert extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $twoMonthsAgo = now()->subMonths(2)->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();
        $thisMonth = now()->startOfMonth();

        $employees = User::where('is_active', true)
            ->where('role', UserRole::Employee)
            ->withCount([
                'attendances as recent_attendance_count' => fn ($query) => $query->whereBetween('captured_at', [$lastMonth, $thisMonth]),
                'attendances as old_attendance_count' => fn ($query) => $query->whereBetween('captured_at', [$twoMonthsAgo, $lastMonth]),
            ])
            ->get();
        $dropped = 0;

        foreach ($employees as $employee) {
            if ($employee->old_attendance_count > 0
                && $employee->recent_attendance_count < $employee->old_attendance_count * 0.5) {
                $dropped++;
            }
        }

        return [
            Stat::make('Pegawai dengan Penurunan Absensi Dinas', $dropped)
                ->description('Perbandingan 2 bulan terakhir')
                ->color($dropped > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
        ];
    }
}
