<?php

namespace App\Filament\Resources\KpiIndicators\Pages;

use App\Filament\Resources\KpiIndicators\KpiIndicatorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKpiIndicators extends ListRecords
{
    protected static string $resource = KpiIndicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
