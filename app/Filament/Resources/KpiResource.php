<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KpiResource\Pages;
use App\Models\Kpi;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class KpiResource extends RoleAwareResource
{
    protected static ?string $model = Kpi::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem Merit';

    protected static ?string $modelLabel = 'KPI';

    protected static ?string $pluralModelLabel = 'KPI';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->label('Nama'),

            TextInput::make('category')
                ->required()
                ->maxLength(255)
                ->label('Kategori'),

            TextInput::make('weight')
                ->required()
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%')
                ->label('Bobot'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama'),

                TextColumn::make('category')
                    ->searchable()
                    ->sortable()
                    ->label('Kategori'),

                TextColumn::make('weight')
                    ->numeric(2)
                    ->suffix('%')
                    ->sortable()
                    ->label('Bobot'),
            ])
            ->defaultSort('category')
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Kpi $record): bool => $record->reviewKpiDetails()->doesntExist()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKpis::route('/'),
        ];
    }
}
