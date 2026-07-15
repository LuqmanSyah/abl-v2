<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Filament\Widgets\EmployeeStats;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;

class EmployeePanelProvider extends RolePanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $this->basePanel($panel)
            ->id('employee')
            ->path('pegawai')
            ->brandName('Portal Pegawai')
            ->resources([DutyTripResource::class, AttendanceResource::class])
            ->widgets([EmployeeStats::class, AccountWidget::class])
            ->colors(['primary' => Color::Blue]);
    }
}
