<?php

namespace App\Filament\Resources\EmployeeKpis\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class EmployeeKpiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('review_period_id')
                    ->label('Periode')
                    ->relationship('reviewPeriod', 'name')
                    ->searchable()->preload()
                    ->required(),
                Select::make('kpi_indicator_id')
                    ->label('Indikator KPI')
                    ->relationship('indicator', 'name')
                    ->searchable()->preload()->required(),
                Select::make('employee_id')
                    ->label('Pegawai')
                    ->relationship('employee', 'name', fn (Builder $query) => auth()->user()->role === UserRole::Manager ? $query->where('manager_id', auth()->id()) : $query)
                    ->searchable()->preload()
                    ->required(),
                Hidden::make('manager_id'),
                TextInput::make('target')
                    ->label('Target')
                    ->required()
                    ->numeric()->minValue(0.01),
                TextInput::make('achievement')
                    ->label('Capaian')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }
}
