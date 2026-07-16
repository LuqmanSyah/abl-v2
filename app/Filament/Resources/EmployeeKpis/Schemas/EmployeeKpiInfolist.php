<?php

namespace App\Filament\Resources\EmployeeKpis\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeKpiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi KPI')
                    ->description('Indikator dan penanggung jawab KPI pegawai.')
                    ->icon('heroicon-o-chart-bar')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('reviewPeriod.name')
                            ->label('Periode'),
                        TextEntry::make('indicator.name')
                            ->label('Indikator KPI'),
                        TextEntry::make('employee.name')
                            ->label('Pegawai'),
                        TextEntry::make('manager.name')
                            ->label('Atasan'),
                    ])
                    ->columnSpanFull(),
                Section::make('Target dan capaian')
                    ->description('Perbandingan hasil aktual terhadap target yang ditetapkan.')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->columns(2)
                    ->schema([
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
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
