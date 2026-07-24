<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
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

        $totalTrips = Attendance::whereBetween('captured_at', [today()->subDays(7), now()])->count();
        $needsReview = Attendance::where('status', AttendanceStatus::NeedsReview)
            ->whereBetween('captured_at', [today()->subDays(7), now()])
            ->count();

        return [
            Stat::make('Pegawai dengan Penurunan Absensi', $dropped)
                ->description('Perbandingan 2 bulan terakhir')
                ->color($dropped > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-exclamation-triangle'),
            Stat::make('Absensi 7 Hari Terakhir', $totalTrips)
                ->description('Total sesi absensi')
                ->color('info')
                ->icon('heroicon-o-map-pin'),
            Stat::make('Perlu Review', $needsReview)
                ->description('Absensi mencurigakan')
                ->color($needsReview > 0 ? 'warning' : 'success')
                ->icon('heroicon-o-shield-exclamation'),
        ];
    }
}
