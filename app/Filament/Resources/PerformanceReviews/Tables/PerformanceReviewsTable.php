<?php

namespace App\Filament\Resources\PerformanceReviews\Tables;

use App\Enums\ReviewType;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PerformanceReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('reviewPeriod.name')
                    ->label('Periode')
                    ->searchable(),
                TextColumn::make('reviewer.name')
                    ->label('Penilai')
                    ->searchable(),
                TextColumn::make('reviewee.name')
                    ->label('Pegawai yang dinilai')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Jenis penilaian')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof ReviewType ? $state->label() : (string) $state)
                    ->searchable(),
                TextColumn::make('score')
                    ->label('Nilai')
                    ->numeric()
                    ->suffix('/5')
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->label('Dikirim pada')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui pada')
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
