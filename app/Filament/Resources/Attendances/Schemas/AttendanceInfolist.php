<?php

namespace App\Filament\Resources\Attendances\Schemas;

use App\Enums\AttendanceStatus;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi absensi')
                    ->description('Ringkasan pelaksanaan absensi pada perintah dinas.')
                    ->icon('heroicon-o-clock')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.name')
                            ->label('Pegawai'),
                        TextEntry::make('dutyTrip.destination')
                            ->label('Dinas'),
                        TextEntry::make('captured_at')
                            ->label('Waktu absensi')
                            ->dateTime(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof AttendanceStatus ? $state->label() : (string) $state)
                            ->color(fn ($state): string => $state instanceof AttendanceStatus ? $state->color() : 'gray'),
                        TextEntry::make('review_reason')
                            ->label('Alasan pemeriksaan awal')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Lokasi dan bukti')
                    ->description('Data GPS dan foto yang dipakai untuk pemeriksaan absensi.')
                    ->icon('heroicon-o-map-pin')
                    ->columns(2)
                    ->schema([
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
                        IconEntry::make('mock_location_suspected')
                            ->label('Lokasi perlu diperiksa')
                            ->boolean()
                            ->columnSpanFull(),
                        ImageEntry::make('photo_path')
                            ->label('Foto absensi')
                            ->getStateUsing(fn ($record): string => route('attendance.photo', $record))
                            ->imageHeight(240)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Sinkronisasi dan riwayat')
                    ->icon('heroicon-o-arrow-path')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('client_uuid')
                            ->label('ID sinkronisasi')
                            ->copyable()
                            ->columnSpanFull(),
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
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }
}
