<?php

namespace App\Filament\Resources\EmployeeKpis\Pages;

use App\Filament\Resources\EmployeeKpis\EmployeeKpiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeKpi extends EditRecord
{
    protected static string $resource = EmployeeKpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
