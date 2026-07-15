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
                    ->label('Duty location')
                    ->placeholder('-'),
                TextEntry::make('destination'),
                TextEntry::make('purpose')
                    ->columnSpanFull(),
                TextEntry::make('starts_at')
                    ->dateTime(),
                TextEntry::make('ends_at')
                    ->dateTime(),
                TextEntry::make('location_name'),
                TextEntry::make('address')
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
                    ->numeric(),
                TextEntry::make('supporting_document_path')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof DutyTripStatus ? $state->label() : (string) $state),
                TextEntry::make('rejection_reason')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('approved_at')
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
