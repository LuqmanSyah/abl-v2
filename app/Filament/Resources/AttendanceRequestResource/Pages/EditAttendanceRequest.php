<?php

namespace App\Filament\Resources\AttendanceRequestResource\Pages;

use App\Filament\Resources\AttendanceRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAttendanceRequest extends EditRecord
{
    protected static string $resource = AttendanceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
