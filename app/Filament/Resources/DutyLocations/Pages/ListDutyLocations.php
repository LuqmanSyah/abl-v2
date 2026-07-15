<?php

namespace App\Filament\Resources\DutyLocations\Pages;

use App\Filament\Resources\DutyLocations\DutyLocationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDutyLocations extends ListRecords
{
    protected static string $resource = DutyLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
