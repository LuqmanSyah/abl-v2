<?php

namespace App\Filament\Widgets;

use App\Enums\DutyTripStatus;
use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HrStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Pegawai aktif', User::where('is_active', true)->count()),
            Stat::make('Pengajuan tertunda', DutyTrip::where('status', DutyTripStatus::Pending)->count()),
            Stat::make('Absensi hari ini', Attendance::whereDate('captured_at', today())->count()),
        ];
    }
}
