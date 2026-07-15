<?php

namespace App\Filament\Resources\DutyTrips\Pages;

use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Models\DutyLocation;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateDutyTrip extends CreateRecord
{
    protected static string $resource = DutyTripResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (User::whereKey($data['employee_id'] ?? null)
            ->where('role', UserRole::Employee)
            ->where('manager_id', auth()->id())
            ->doesntExist()) {
            throw ValidationException::withMessages([
                'employee_id' => 'Pegawai harus merupakan bawahan langsung Anda.',
            ]);
        }

        $data['manager_id'] = auth()->id();
        $data['status'] = DutyTripStatus::Approved->value;
        $data['approved_at'] = now();

        if ($location = DutyLocation::where('is_active', true)->find($data['duty_location_id'] ?? null)) {
            $data = [...$data, ...$location->only(['address', 'latitude', 'longitude', 'radius_meters'])];
            $data['location_name'] = $location->name;
        }

        return $data;
    }
}
