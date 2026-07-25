<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Enums\LeaveStatus;
use App\Filament\Resources\LeaveRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] ??= LeaveStatus::Pending->value;

        return $data;
    }
}
