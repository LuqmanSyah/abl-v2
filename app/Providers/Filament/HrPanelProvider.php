<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Resources\DutyLocations\DutyLocationResource;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Filament\Resources\Positions\PositionResource;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\HrStats;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;

class HrPanelProvider extends RolePanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $this->basePanel($panel)
            ->default()
            ->id('hr')
            ->path('hr')
            ->brandName('Portal SDM/HR')
            ->resources([
                UserResource::class,
                UnitResource::class,
                PositionResource::class,
                DutyLocationResource::class,
                DutyTripResource::class,
                AttendanceResource::class,
            ])
            ->widgets([HrStats::class, AccountWidget::class])
            ->colors(['primary' => Color::Amber]);
    }
}
