<?php

namespace App\Filament\Resources\KpiIndicators\Schemas;

use App\Models\KpiIndicator;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class KpiIndicatorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('review_period_id')
                    ->label('Periode')
                    ->relationship('reviewPeriod', 'name', fn (Builder $query) => $query
                        ->whereDate('ends_at', '>=', today())
                        ->whereDoesntHave('meritResults', fn (Builder $query) => $query->whereNotNull('published_at')))
                    ->searchable()->preload()
                    ->disabled(fn (?KpiIndicator $record): bool => $record?->employeeKpis()->exists() ?? false)
                    ->required(),
                TextInput::make('name')
                    ->label('Indikator')
                    ->required(),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->columnSpanFull(),
                TextInput::make('unit')->label('Satuan'),
                TextInput::make('weight')
                    ->label('Bobot (%)')
                    ->required()
                    ->numeric()->minValue(1)->maxValue(100),
            ]);
    }
}
