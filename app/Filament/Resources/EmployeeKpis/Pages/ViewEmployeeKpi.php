<?php

namespace App\Filament\Resources\EmployeeKpis\Pages;

use App\Filament\Resources\EmployeeKpis\EmployeeKpiResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployeeKpi extends ViewRecord
{
    protected static string $resource = EmployeeKpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
