<?php

namespace App\Filament\Resources\DevelopmentPlans\Pages;

use App\Filament\Resources\DevelopmentPlans\DevelopmentPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDevelopmentPlans extends ListRecords
{
    protected static string $resource = DevelopmentPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn (): bool => DevelopmentPlanResource::canCreate()),
        ];
    }
}
