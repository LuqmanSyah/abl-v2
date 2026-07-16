<?php

namespace App\Filament\Resources\EmployeeKpis\Pages;

use App\Filament\Resources\EmployeeKpis\EmployeeKpiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeKpis extends ListRecords
{
    protected static string $resource = EmployeeKpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => EmployeeKpiResource::canCreate()),
        ];
    }
}
