<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Enums\LeaveStatus;
use App\Filament\Resources\LeaveRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateLeaveRequest extends CreateRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'user_id' => Auth::id(),
            'status' => LeaveStatus::Pending->value,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }
}
