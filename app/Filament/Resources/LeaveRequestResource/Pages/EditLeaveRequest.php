<?php

namespace App\Filament\Resources\LeaveRequestResource\Pages;

use App\Enums\LeaveStatus;
use App\Filament\Resources\LeaveRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveRequest extends EditRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => $this->record->status === LeaveStatus::Pending),
        ];
    }
}
