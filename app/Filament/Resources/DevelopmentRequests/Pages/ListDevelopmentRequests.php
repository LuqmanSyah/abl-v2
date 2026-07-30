<?php

namespace App\Filament\Resources\DevelopmentRequests\Pages;

use App\Filament\Resources\DevelopmentRequests\DevelopmentRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDevelopmentRequests extends ListRecords
{
    protected static string $resource = DevelopmentRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn (): bool => DevelopmentRequestResource::canCreate()),
        ];
    }
}
