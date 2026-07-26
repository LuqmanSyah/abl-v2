<?php

namespace App\Filament\Resources\IndividualDevelopmentPlanResource\Pages;

use App\Filament\Resources\IndividualDevelopmentPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIndividualDevelopmentPlans extends ListRecords
{
    protected static string $resource = IndividualDevelopmentPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
