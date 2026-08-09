<?php

namespace App\Filament\Resources\EmployeeKpis\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                    ->relationship('reviewPeriod', 'name', fn (Builder $query) => $query
                        ->where('is_active', true)
                        ->whereDate('ends_at', '>=', today())
                        ->whereDoesntHave('meritResults', fn (Builder $query) => $query->whereNotNull('published_at')))
                    ->searchable()->preload()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('kpi_indicator_id', null))
                    ->required(),
                Select::make('kpi_indicator_id')
                    ->label('Indikator KPI')
                    ->relationship('indicator', 'name', fn (Builder $query, Get $get) => $query
                        ->where('review_period_id', $get('review_period_id')))
                    ->disabled(fn (Get $get): bool => blank($get('review_period_id')))
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
                    ->numeric()->minValue(0)
                    ->helperText('Capaian di atas target dibatasi maksimal 120% dalam perhitungan merit.')
                    ->default(0),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }
}
