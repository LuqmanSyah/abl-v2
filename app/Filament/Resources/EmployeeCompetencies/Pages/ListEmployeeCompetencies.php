<?php

namespace App\Filament\Resources\EmployeeCompetencies\Pages;

use App\Filament\Resources\EmployeeCompetencies\EmployeeCompetencyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeCompetencies extends ListRecords
{
    protected static string $resource = EmployeeCompetencyResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
