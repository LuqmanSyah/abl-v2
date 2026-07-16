<?php

namespace App\Filament\Resources\TrainingRequests\Pages;

use App\Filament\Resources\TrainingRequests\TrainingRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrainingRequests extends ListRecords
{
    protected static string $resource = TrainingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => TrainingRequestResource::canCreate()),
        ];
    }
}
