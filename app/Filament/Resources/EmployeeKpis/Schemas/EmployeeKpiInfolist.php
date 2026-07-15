<?php

namespace App\Filament\Resources\EmployeeKpis\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class EmployeeKpiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reviewPeriod.name')
                    ->label('Review period'),
                TextEntry::make('kpi_indicator_id')
                    ->numeric(),
                TextEntry::make('employee.name')
                    ->label('Employee'),
                TextEntry::make('manager.name')
                    ->label('Manager'),
                TextEntry::make('target')
                    ->numeric(),
                TextEntry::make('achievement')
                    ->numeric(),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
