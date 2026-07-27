<?php

namespace App\Filament\Resources\AttendanceRequestResource\Pages;

use App\Filament\Resources\AttendanceRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAttendanceRequests extends ListRecords
{
    protected static string $resource = AttendanceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
