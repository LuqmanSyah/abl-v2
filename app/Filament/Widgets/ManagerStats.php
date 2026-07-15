<?php

namespace App\Filament\Widgets;

use App\Enums\DutyTripStatus;
use App\Models\Attendance;
use App\Models\DutyTrip;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ManagerStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Perlu persetujuan', DutyTrip::where('manager_id', auth()->id())->where('status', DutyTripStatus::Pending)->count()),
            Stat::make('Total pengajuan bawahan', DutyTrip::where('manager_id', auth()->id())->count()),
            Stat::make('Absensi bawahan', Attendance::whereHas('dutyTrip', fn ($query) => $query->where('manager_id', auth()->id()))->count()),
        ];
    }
}
