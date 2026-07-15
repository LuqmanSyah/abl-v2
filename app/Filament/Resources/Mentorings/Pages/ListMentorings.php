<?php

namespace App\Filament\Resources\Mentorings\Pages;

use App\Filament\Resources\Mentorings\MentoringResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMentorings extends ListRecords
{
    protected static string $resource = MentoringResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
