<?php

namespace App\Filament\Resources\DutyTrips\Tables;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use Filament\Actions\Action;
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
                Action::make('verify_attendance')
                    ->label('Verifikasi Absensi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi Absensi Dinas')
                    ->modalDescription('Status absensi akan diubah menjadi Valid dan dipakai dalam perhitungan kedisiplinan.')
                    ->modalSubmitActionLabel('Verifikasi Absensi')
                    ->modalWidth('md')
                    ->visible(fn ($record): bool => auth()->user()?->role === UserRole::Hr
                        && $record->attendance?->status === AttendanceStatus::NeedsReview)
                    ->action(fn ($record) => $record->attendance->verifyByHr(auth()->user()))
                    ->successNotificationTitle('Absensi dinas berhasil diverifikasi'),
            ])
            ->defaultSort('starts_at', 'desc');
    }
}
