<?php

namespace App\Filament\Resources\EmployeeKpis\Pages;

use App\Filament\Resources\EmployeeKpis\EmployeeKpiResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeKpi extends CreateRecord
{
    protected static string $resource = EmployeeKpiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();

        return $data;
    }
}
