<?php

namespace App\Filament\Resources\MeritResults\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MeritResultInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reviewPeriod.name')
                    ->label('Review period'),
                TextEntry::make('employee.name')
                    ->label('Employee'),
                TextEntry::make('kpi_score')
                    ->numeric(),
                TextEntry::make('discipline_score')
                    ->numeric(),
                TextEntry::make('manager_score')
                    ->numeric(),
                TextEntry::make('review_360_score')
                    ->numeric(),
                TextEntry::make('total_score')
                    ->numeric(),
                TextEntry::make('estimated_bonus')
                    ->numeric(),
                TextEntry::make('manager_verified_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('manager_verified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('hr_verified_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('hr_verified_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('published_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
