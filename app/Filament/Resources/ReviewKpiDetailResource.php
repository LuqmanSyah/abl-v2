<?php

namespace App\Filament\Resources;

use App\Models\ReviewKpiDetail;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

class ReviewKpiDetailResource extends Resource
{
    protected static ?string $model = ReviewKpiDetail::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->components(static::formComponents());
    }

    /**
     * @return array<Component>
     */
    public static function formComponents(): array
    {
        return [
            Select::make('kpi_id')
                ->relationship('kpi', 'name')
                ->disabled()
                ->required()
                ->label('KPI'),

            TextInput::make('weight')
                ->disabled()
                ->suffix('%')
                ->label('Bobot'),

            TextInput::make('self_score')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->label('Nilai Diri'),

            Textarea::make('self_notes')
                ->maxLength(1000)
                ->label('Catatan Diri'),

            TextInput::make('manager_score')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->label('Nilai Manager'),

            Textarea::make('manager_notes')
                ->maxLength(1000)
                ->label('Catatan Manager'),

            TextInput::make('subtotal_score')
                ->disabled()
                ->suffix(' poin')
                ->label('Subtotal'),
        ];
    }
}
