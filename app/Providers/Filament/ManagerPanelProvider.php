<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Filament\Widgets\ManagerStats;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;

class ManagerPanelProvider extends RolePanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $this->basePanel($panel)
            ->id('manager')
            ->path('atasan')
            ->brandName('Portal Atasan')
            ->resources([DutyTripResource::class, AttendanceResource::class])
            ->widgets([ManagerStats::class, AccountWidget::class])
            ->colors(['primary' => Color::Green]);
    }
}
