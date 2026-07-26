<?php

namespace App\Filament\Resources\UserSkillResource\Pages;

use App\Filament\Resources\UserSkillResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUserSkills extends ListRecords
{
    protected static string $resource = UserSkillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
