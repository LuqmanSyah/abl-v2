<?php

namespace App\Filament\Resources\DutyTrips\Pages;

use App\Enums\DutyTripStatus;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Models\DutyTrip;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateDutyTrip extends CreateRecord
{
    protected static string $resource = DutyTripResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();
        $data['status'] = DutyTripStatus::Active->value;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): DutyTrip {
            $employeeIds = $data['employee_ids'];
            unset($data['employee_ids']);

            $record = DutyTrip::create([
                ...$data,
                'employee_id' => array_shift($employeeIds),
            ]);

            foreach ($employeeIds as $employeeId) {
                DutyTrip::create([
                    ...$data,
                    'employee_id' => $employeeId,
                ]);
            }

            return $record;
        });
    }
}
