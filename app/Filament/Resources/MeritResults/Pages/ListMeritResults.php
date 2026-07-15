<?php

namespace App\Filament\Resources\MeritResults\Pages;

use App\Filament\Resources\MeritResults\MeritResultResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMeritResults extends ListRecords
{
    protected static string $resource = MeritResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
