<?php

namespace App\Filament\Widgets;

use App\Enums\DutyTripStatus;
use App\Models\Attendance;
use App\Models\DutyTrip;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmployeeStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Menunggu persetujuan', DutyTrip::where('employee_id', auth()->id())->where('status', DutyTripStatus::Pending)->count()),
            Stat::make('Dinas disetujui', DutyTrip::where('employee_id', auth()->id())->where('status', DutyTripStatus::Approved)->count()),
            Stat::make('Riwayat absensi', Attendance::where('employee_id', auth()->id())->count()),
        ];
    }
}
