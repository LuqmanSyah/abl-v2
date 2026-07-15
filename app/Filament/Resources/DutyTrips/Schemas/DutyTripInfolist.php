<?php

namespace App\Filament\Resources\DutyTrips\Schemas;

use App\Enums\DutyTripStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Schema;

class DutyTripInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('employee.name')
                    ->label('Pegawai'),
                TextEntry::make('manager.name')
                    ->label('Atasan'),
                TextEntry::make('dutyLocation.name')
                    ->label('Lokasi terdaftar')
                    ->placeholder('-'),
                TextEntry::make('destination')
                    ->label('Tujuan dinas'),
                TextEntry::make('purpose')
                    ->label('Keperluan')
                    ->columnSpanFull(),
                TextEntry::make('starts_at')
                    ->label('Mulai')
                    ->dateTime(),
                TextEntry::make('ends_at')
                    ->label('Selesai')
                    ->dateTime(),
                TextEntry::make('location_name')
                    ->label('Nama lokasi'),
                TextEntry::make('address')
                    ->label('Alamat')
                    ->columnSpanFull(),
                ViewEntry::make('map')
                    ->label('Titik lokasi')
                    ->view('filament.infolists.duty-trip-map')
                    ->columnSpanFull(),
                TextEntry::make('latitude')
                    ->numeric(),
                TextEntry::make('longitude')
                    ->numeric(),
                TextEntry::make('radius_meters')
                    ->label('Radius (meter)')
                    ->numeric(),
                TextEntry::make('supporting_document_path')
                    ->label('Dokumen pendukung')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof DutyTripStatus ? $state->label() : (string) $state),
                TextEntry::make('approved_at')
                    ->label('Ditugaskan pada')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
