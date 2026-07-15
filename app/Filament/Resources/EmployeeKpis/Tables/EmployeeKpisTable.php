<?php

namespace App\Filament\Resources\EmployeeKpis\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeeKpisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reviewPeriod.name')
                    ->label('Periode')
                    ->searchable(),
                TextColumn::make('indicator.name')
                    ->label('Indikator KPI')
                    ->searchable(),
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable(),
                TextColumn::make('manager.name')
                    ->label('Atasan')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('target')
                    ->label('Target')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('achievement')
                    ->label('Capaian')
                    ->numeric()
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
