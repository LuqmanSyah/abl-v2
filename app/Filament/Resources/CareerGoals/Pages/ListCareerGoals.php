<?php

namespace App\Filament\Resources\CareerGoals\Pages;

use App\Filament\Resources\CareerGoals\CareerGoalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCareerGoals extends ListRecords
{
    protected static string $resource = CareerGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => CareerGoalResource::canCreate()),
        ];
    }
}
