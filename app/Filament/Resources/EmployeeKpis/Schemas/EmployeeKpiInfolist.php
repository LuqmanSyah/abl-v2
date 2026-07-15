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
                    ->label('Periode'),
                TextEntry::make('indicator.name')
                    ->label('Indikator KPI'),
                TextEntry::make('employee.name')
                    ->label('Pegawai'),
                TextEntry::make('manager.name')
                    ->label('Atasan'),
                TextEntry::make('target')
                    ->label('Target')
                    ->numeric(),
                TextEntry::make('achievement')
                    ->label('Capaian')
                    ->numeric(),
                TextEntry::make('notes')
                    ->label('Catatan')
                    ->placeholder('Tidak ada catatan.')
                    ->columnSpanFull(),
            ]);
    }
}
