<?php

namespace App\Filament\Resources\DutyTrips\Tables;

use App\Enums\DutyTripStatus;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DutyTripsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable(),
                TextColumn::make('manager.name')
                    ->label('Atasan')
                    ->searchable(),
                TextColumn::make('destination')
                    ->label('Tujuan')
                    ->searchable(),
                TextColumn::make('starts_at')
                    ->label('Mulai')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Selesai')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('location_name')
                    ->label('Lokasi')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof DutyTripStatus ? $state->label() : (string) $state)
                    ->color(fn ($state): string => $state instanceof DutyTripStatus ? $state->color() : 'gray')
                    ->searchable(),
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
                SelectFilter::make('status')
                    ->label('Status dinas')
                    ->options(DutyTripStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn ($record): bool => DutyTripResource::canEdit($record)),
            ])
            ->defaultSort('starts_at', 'desc');
    }
}
