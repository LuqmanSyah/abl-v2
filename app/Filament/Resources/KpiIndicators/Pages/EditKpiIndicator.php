<?php

namespace App\Filament\Resources\KpiIndicators\Pages;

use App\Filament\Resources\KpiIndicators\KpiIndicatorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKpiIndicator extends EditRecord
{
    protected static string $resource = KpiIndicatorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
