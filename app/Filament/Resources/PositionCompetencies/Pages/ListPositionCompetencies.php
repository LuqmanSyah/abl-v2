<?php

namespace App\Filament\Resources\PositionCompetencies\Pages;

use App\Filament\Resources\PositionCompetencies\PositionCompetencyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPositionCompetencies extends ListRecords
{
    protected static string $resource = PositionCompetencyResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
