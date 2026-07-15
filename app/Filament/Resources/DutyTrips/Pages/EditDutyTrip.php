<?php

namespace App\Filament\Resources\DutyTrips\Pages;

use App\Filament\Resources\DutyTrips\DutyTripResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDutyTrip extends EditRecord
{
    protected static string $resource = DutyTripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
