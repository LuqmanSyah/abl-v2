<?php

namespace App\Filament\Resources\ReviewPeriods\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReviewPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama periode')
                    ->required(),
                DatePicker::make('starts_at')
                    ->label('Mulai')
                    ->required(),
                DatePicker::make('ends_at')
                    ->label('Selesai')
                    ->afterOrEqual('starts_at')
                    ->required(),
                TextInput::make('kpi_weight')
                    ->label('Bobot KPI (%)')
                    ->required()
                    ->numeric()
                    ->minValue(0)->maxValue(100)
                    ->default(40),
                TextInput::make('discipline_weight')
                    ->label('Bobot kedisiplinan (%)')
                    ->required()
                    ->numeric()->minValue(0)->maxValue(100)
                    ->default(20),
                TextInput::make('manager_weight')
                    ->label('Bobot penilaian Atasan (%)')
                    ->required()
                    ->numeric()->minValue(0)->maxValue(100)
                    ->default(20),
                TextInput::make('review_360_weight')
                    ->label('Bobot penilaian 360 (%)')
                    ->required()
                    ->numeric()->minValue(0)->maxValue(100)
                    ->default(20),
                TextInput::make('base_bonus')
                    ->label('Dasar estimasi bonus')
                    ->prefix('Rp')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }
}
