<?php

namespace App\Filament\Resources\DutyTrips\Schemas;

use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Filament\Forms\Components\MapPicker;
use App\Models\DutyLocation;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class DutyTripForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Penugasan')
                    ->description('Tentukan pegawai, tujuan, dan waktu pelaksanaan dinas.')
                    ->icon('heroicon-o-briefcase')
                    ->columnSpanFull()
                    ->columns(2)
                    ->components([
                        Select::make('employee_id')
                            ->label('Pegawai yang ditugaskan')
                            ->relationship('employee', 'name', fn (Builder $query) => $query
                                ->where('role', UserRole::Employee)
                                ->where('manager_id', auth()->id()))
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('destination')
                            ->label('Tujuan dinas')
                            ->placeholder('Contoh: Rapat koordinasi proyek')
                            ->required(),
                        Textarea::make('purpose')
                            ->label('Keperluan')
                            ->placeholder('Jelaskan hasil yang diharapkan dari dinas ini')
                            ->required()
                            ->columnSpanFull(),
                        DatePicker::make('starts_at')
                            ->label('Tanggal mulai')
                            ->dehydrateStateUsing(fn (string $state): Carbon => Carbon::parse($state)->startOfDay())
                            ->required(),
                        DatePicker::make('ends_at')
                            ->label('Tanggal selesai')
                            ->afterOrEqual('starts_at')
                            ->dehydrateStateUsing(fn (string $state): Carbon => Carbon::parse($state)->endOfDay())
                            ->required(),
                        FileUpload::make('supporting_document_path')
                            ->label('Dokumen pendukung')
                            ->disk('local')
                            ->directory('duty-trip-documents')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120)
                            ->helperText('PDF, JPG, atau PNG. Maksimal 5 MB.')
                            ->columnSpanFull(),
                        Hidden::make('manager_id'),
                        Hidden::make('status')->default(DutyTripStatus::Approved->value),
                    ]),
                Section::make('Lokasi absensi')
                    ->description('Pilih lokasi tersimpan atau tentukan titik baru. Data lokasi tersimpan akan mengisi form otomatis.')
                    ->icon('heroicon-o-map-pin')
                    ->columnSpanFull()
                    ->columns(2)
                    ->components([
                        Select::make('duty_location_id')
                            ->label('Lokasi tersimpan')
                            ->relationship('dutyLocation', 'name', fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                $location = DutyLocation::find($state);
                                if (! $location) {
                                    return;
                                }

                                $set('location_name', $location->name);
                                $set('address', $location->address);
                                $set('latitude', $location->latitude);
                                $set('longitude', $location->longitude);
                                $set('radius_meters', $location->radius_meters);
                            })
                            ->helperText('Opsional. Kosongkan bila lokasi belum terdaftar.')
                            ->columnSpanFull(),
                        MapPicker::make('map_picker')
                            ->label('Cari atau pilih titik pada peta')
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        TextInput::make('location_name')
                            ->label('Nama lokasi')
                            ->placeholder('Contoh: Kantor Cabang Jakarta')
                            ->required(),
                        TextInput::make('radius_meters')
                            ->label('Batas jarak absensi (meter)')
                            ->helperText('Pegawai di luar jarak ini akan ditandai untuk diperiksa.')
                            ->required()
                            ->numeric()
                            ->minValue(10)
                            ->default(100),
                        Textarea::make('address')
                            ->label('Alamat lengkap')
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('latitude')
                            ->label('Garis lintang')
                            ->required()
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90),
                        TextInput::make('longitude')
                            ->label('Garis bujur')
                            ->required()
                            ->numeric()
                            ->minValue(-180)
                            ->maxValue(180),
                    ]),
            ]);
    }
}
