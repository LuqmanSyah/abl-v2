<?php

namespace App\Filament\Resources\DutyTrips\Pages;

use App\Enums\DutyTripStatus;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Models\DutyLocation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateDutyTrip extends CreateRecord
{
    protected static string $resource = DutyTripResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()->manager_id) {
            throw ValidationException::withMessages([
                'manager_id' => 'Atasan belum ditetapkan. Hubungi Admin SDM/HR.',
            ]);
        }

        $data['employee_id'] = auth()->id();
        $data['manager_id'] = auth()->user()->manager_id;
        $data['status'] = DutyTripStatus::Pending->value;

        if ($location = DutyLocation::find($data['duty_location_id'] ?? null)) {
            $data = [...$data, ...$location->only(['name', 'address', 'latitude', 'longitude', 'radius_meters'])];
            $data['location_name'] = $location->name;
        }

        return $data;
    }
}
