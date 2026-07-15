<?php

namespace App\Filament\Resources\DutyTrips\Schemas;

use App\Enums\DutyTripStatus;
use App\Filament\Forms\Components\MapPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DutyTripForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('employee_id'),
                Hidden::make('manager_id'),
                Select::make('duty_location_id')
                    ->label('Lokasi terdaftar')
                    ->relationship('dutyLocation', 'name', fn ($query) => $query->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->helperText('Opsional. Koordinat lokasi terdaftar disalin saat pengajuan dibuat.'),
                MapPicker::make('map_picker')
                    ->label('Pilih titik lokasi')
                    ->dehydrated(false)
                    ->columnSpanFull(),
                TextInput::make('destination')
                    ->label('Tujuan dinas')
                    ->required(),
                Textarea::make('purpose')
                    ->label('Keperluan')
                    ->required()
                    ->columnSpanFull(),
                DateTimePicker::make('starts_at')
                    ->label('Mulai')
                    ->native(false)
                    ->required(),
                DateTimePicker::make('ends_at')
                    ->label('Selesai')
                    ->native(false)
                    ->after('starts_at')
                    ->required(),
                TextInput::make('location_name')
                    ->label('Nama lokasi')
                    ->required(),
                Textarea::make('address')
                    ->label('Alamat')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('latitude')
                    ->label('Latitude')
                    ->required()
                    ->numeric()
                    ->minValue(-90)
                    ->maxValue(90),
                TextInput::make('longitude')
                    ->label('Longitude')
                    ->required()
                    ->numeric()
                    ->minValue(-180)
                    ->maxValue(180),
                TextInput::make('radius_meters')
                    ->label('Radius geofence (meter)')
                    ->required()
                    ->numeric()
                    ->minValue(10)
                    ->default(100),
                FileUpload::make('supporting_document_path')
                    ->label('Dokumen pendukung')
                    ->disk('local')
                    ->directory('duty-trip-documents')
                    ->maxSize(5120),
                Hidden::make('status')->default(DutyTripStatus::Pending->value),
            ]);
    }
}
