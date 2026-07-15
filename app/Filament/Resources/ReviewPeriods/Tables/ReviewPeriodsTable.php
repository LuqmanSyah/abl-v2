<?php

namespace App\Filament\Resources\ReviewPeriods\Tables;

use App\Models\EmployeeKpi;
use App\Services\MeritCalculator;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReviewPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->date()
                    ->sortable(),
                TextColumn::make('kpi_weight')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discipline_weight')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('manager_weight')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('review_360_weight')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('base_bonus')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('calculate')
                    ->label('Hitung Merit')
                    ->icon('heroicon-o-calculator')
                    ->requiresConfirmation()
                    ->action(function ($record): void {
                        $employees = EmployeeKpi::with('employee')->where('review_period_id', $record->id)
                            ->get()->pluck('employee')->unique('id');
                        foreach ($employees as $employee) {
                            app(MeritCalculator::class)->calculate($record, $employee);
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
