<?php

namespace App\Filament\Resources\DutyTrips\Schemas;

use App\Enums\DutyTripStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DutyTripInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi penugasan')
                    ->description('Pegawai, tujuan, waktu, dan status perintah dinas.')
                    ->icon('heroicon-o-briefcase')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('employee.name')
                            ->label('Pegawai'),
                        TextEntry::make('manager.name')
                            ->label('Atasan'),
                        TextEntry::make('destination')
                            ->label('Tujuan dinas'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof DutyTripStatus ? $state->label() : (string) $state)
                            ->color(fn ($state): string => $state instanceof DutyTripStatus ? $state->color() : 'gray'),
                        TextEntry::make('starts_at')
                            ->label('Mulai')
                            ->dateTime(),
                        TextEntry::make('ends_at')
                            ->label('Selesai')
                            ->dateTime(),
                        TextEntry::make('purpose')
                            ->label('Keperluan')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Lokasi absensi dinas')
                    ->description('Titik dan radius yang menjadi acuan validasi absensi dinas.')
                    ->icon('heroicon-o-map-pin')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('dutyLocation.name')
                            ->label('Lokasi terdaftar')
                            ->placeholder('-'),
                        TextEntry::make('location_name')
                            ->label('Nama lokasi'),
                        TextEntry::make('address')
                            ->label('Alamat')
                            ->columnSpanFull(),
                        TextEntry::make('latitude')
                            ->label('Garis lintang')
                            ->numeric(),
                        TextEntry::make('longitude')
                            ->label('Garis bujur')
                            ->numeric(),
                        TextEntry::make('radius_meters')
                            ->label('Radius (meter)')
                            ->numeric()
                            ->columnSpanFull(),
                        ViewEntry::make('map')
                            ->label('Titik lokasi')
                            ->view('filament.infolists.duty-trip-map')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make('Riwayat')
                    ->icon('heroicon-o-clock')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('approved_at')
                            ->label('Ditugaskan pada')
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
