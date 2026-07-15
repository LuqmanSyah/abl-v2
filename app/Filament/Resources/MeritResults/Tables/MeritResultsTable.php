<?php

namespace App\Filament\Resources\MeritResults\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MeritResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reviewPeriod.name')
                    ->label('Periode')
                    ->searchable(),
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable(),
                TextColumn::make('kpi_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discipline_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('manager_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('review_360_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_score')
                    ->label('Skor merit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estimated_bonus')
                    ->label('Estimasi bonus')
                    ->money('IDR')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('manager_verified_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('manager_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('hr_verified_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('hr_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
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
                ViewAction::make(),
            ]);
    }
}
