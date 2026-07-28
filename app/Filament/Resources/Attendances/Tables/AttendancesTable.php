<?php

namespace App\Filament\Resources\Attendances\Tables;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('dutyTrip.destination')
                    ->label('Dinas')
                    ->searchable(),
                TextColumn::make('employee.name')
                    ->label('Pegawai')
                    ->searchable(),
                TextColumn::make('captured_at')
                    ->label('Waktu absensi')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('latitude')
                    ->label('Garis lintang')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('longitude')
                    ->label('Garis bujur')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('accuracy_meters')
                    ->label('Akurasi GPS (m)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('distance_meters')
                    ->label('Jarak (m)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AttendanceStatus ? $state->label() : (string) $state)
                    ->color(fn ($state): string => $state instanceof AttendanceStatus ? $state->color() : 'gray')
                    ->searchable(),
                IconColumn::make('mock_location_suspected')
                    ->label('GPS mencurigakan')
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
                SelectFilter::make('status')
                    ->label('Status absensi')
                    ->options(AttendanceStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Verifikasi Absensi')
                    ->modalDescription('Status absensi akan diubah menjadi Valid dan dipakai dalam perhitungan kedisiplinan.')
                    ->modalSubmitActionLabel('Verifikasi Absensi')
                    ->modalWidth('md')
                    ->visible(fn ($record): bool => auth()->user()?->role === UserRole::Hr
                        && $record->status === AttendanceStatus::NeedsReview)
                    ->action(fn ($record) => $record->verifyByHr(auth()->user()))
                    ->successNotificationTitle('Absensi berhasil diverifikasi'),
            ])
            ->defaultSort('captured_at', 'desc');
    }
}
