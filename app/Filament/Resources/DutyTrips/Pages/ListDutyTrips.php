<?php

namespace App\Filament\Resources\DutyTrips\Pages;

use App\Filament\Resources\DutyTrips\DutyTripResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDutyTrips extends ListRecords
{
    protected static string $resource = DutyTripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Buat Perintah Dinas')
                ->visible(fn (): bool => DutyTripResource::canCreate()),
        ];
    }
}
