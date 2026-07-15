<?php

namespace App\Filament\Resources\DutyLocations\Pages;

use App\Filament\Resources\DutyLocations\DutyLocationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDutyLocation extends EditRecord
{
    protected static string $resource = DutyLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
