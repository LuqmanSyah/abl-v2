<?php

namespace App\Filament\Resources\KpiResource\Pages;

use App\Filament\Resources\KpiResource;
use App\Models\Kpi;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;

class ListKpis extends ListRecords
{
    protected static string $resource = KpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->databaseTransaction(),
            Action::make('rebalance')
                ->label('Atur Ulang Bobot')
                ->schema(fn (): array => Kpi::query()
                    ->orderBy('id')
                    ->get()
                    ->map(fn (Kpi $kpi): TextInput => TextInput::make("weights.{$kpi->id}")
                        ->label($kpi->name)
                        ->default($kpi->weight)
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%'))
                    ->all())
                ->action(fn (array $data) => Kpi::rebalance($data['weights']))
                ->visible(fn (): bool => Kpi::query()->exists()),
        ];
    }
}
