<?php

namespace App\Filament\Resources\Attendances\Schemas;

use App\Enums\AttendanceStatus;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AttendanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('client_uuid')
                    ->label('ID sinkronisasi')
                    ->copyable(),
                TextEntry::make('dutyTrip.destination')
                    ->label('Dinas'),
                TextEntry::make('employee.name')
                    ->label('Pegawai'),
                TextEntry::make('captured_at')
                    ->label('Waktu absensi')
                    ->dateTime(),
                TextEntry::make('latitude')
                    ->label('Garis lintang')
                    ->numeric(),
                TextEntry::make('longitude')
                    ->label('Garis bujur')
                    ->numeric(),
                TextEntry::make('accuracy_meters')
                    ->label('Akurasi GPS (meter)')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('distance_meters')
                    ->label('Jarak dari lokasi (meter)')
                    ->numeric(),
                ImageEntry::make('photo_path')
                    ->label('Foto')
                    ->getStateUsing(fn ($record): string => route('attendance.photo', $record))
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof AttendanceStatus ? $state->label() : (string) $state)
                    ->color(fn ($state): string => $state instanceof AttendanceStatus ? $state->color() : 'gray'),
                IconEntry::make('mock_location_suspected')
                    ->label('Lokasi perlu diperiksa')
                    ->boolean(),
                TextEntry::make('synced_at')
                    ->label('Tersinkron pada')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label('Dibuat pada')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Diperbarui pada')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
