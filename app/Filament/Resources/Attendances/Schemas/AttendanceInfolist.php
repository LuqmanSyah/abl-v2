<?php

namespace App\Filament\Resources\Attendances\Schemas;

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
                TextEntry::make('client_uuid'),
                TextEntry::make('dutyTrip.id')
                    ->label('Duty trip'),
                TextEntry::make('employee.name')
                    ->label('Employee'),
                TextEntry::make('captured_at')
                    ->dateTime(),
                TextEntry::make('latitude')
                    ->numeric(),
                TextEntry::make('longitude')
                    ->numeric(),
                TextEntry::make('accuracy_meters')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('distance_meters')
                    ->numeric(),
                ImageEntry::make('photo_path')
                    ->label('Foto')
                    ->getStateUsing(fn ($record): string => route('attendance.photo', $record))
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->badge(),
                IconEntry::make('mock_location_suspected')
                    ->boolean(),
                TextEntry::make('synced_at')
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
