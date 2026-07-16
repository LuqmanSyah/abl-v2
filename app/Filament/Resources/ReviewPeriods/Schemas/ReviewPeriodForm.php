<?php

namespace App\Filament\Resources\ReviewPeriods\Schemas;

use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->integer()
                    ->minValue(0)->maxValue(100)
                    ->default(40),
                TextInput::make('discipline_weight')
                    ->label('Bobot kedisiplinan (%)')
                    ->required()
                    ->integer()->minValue(0)->maxValue(100)
                    ->default(20),
                TextInput::make('manager_weight')
                    ->label('Bobot penilaian Atasan (%)')
                    ->required()
                    ->integer()->minValue(0)->maxValue(100)
                    ->default(20),
                TextInput::make('review_360_weight')
                    ->label('Bobot umpan balik kinerja (%)')
                    ->required()
                    ->integer()->minValue(0)->maxValue(100)
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            $weights = [
                                $get('kpi_weight'),
                                $get('discipline_weight'),
                                $get('manager_weight'),
                                $value,
                            ];

                            if (in_array(null, $weights, true) || in_array('', $weights, true)) {
                                return;
                            }

                            if (array_sum(array_map('intval', $weights)) !== 100) {
                                $fail('Total bobot merit wajib 100%.');
                            }
                        },
                    ])
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
