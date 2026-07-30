<?php

namespace App\Filament\Resources\DutyTrips\Pages;

use App\Enums\DutyTripStatus;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDutyTrip extends CreateRecord
{
    protected static string $resource = DutyTripResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();
        $data['status'] = DutyTripStatus::Active->value;

        return $data;
    }
}
