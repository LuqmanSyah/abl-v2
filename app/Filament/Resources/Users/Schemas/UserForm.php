<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(),
                TextInput::make('password')
                    ->label('Kata sandi')
                    ->password()
                    ->revealable()
                    ->rule(Password::min(8)->mixedCase()->numbers())
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('Minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka.'),
                Select::make('role')
                    ->label('Peran')
                    ->options(UserRole::options())
                    ->default(UserRole::Employee->value)
                    ->live()
                    ->afterStateUpdated(function (mixed $state, Set $set): void {
                        if ($state !== UserRole::Employee->value) {
                            $set('manager_id', null);
                        }
                    })
                    ->required(),
                Select::make('unit_id')
                    ->label('Unit kerja')
                    ->relationship('unit', 'name')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('position_id', null)),
                Select::make('position_id')
                    ->label('Jabatan')
                    ->relationship('position', 'name', fn (Builder $query, Get $get) => $query->when(
                        $get('unit_id'),
                        fn (Builder $query, mixed $unitId) => $query->where('unit_id', $unitId),
                    ))
                    ->searchable()
                    ->preload()
                    ->disabled(fn (Get $get): bool => blank($get('unit_id')))
                    ->helperText('Pilih unit kerja terlebih dahulu.'),
                Select::make('manager_id')
                    ->label('Atasan')
                    ->relationship('manager', 'name', fn (Builder $query) => $query
                        ->where('role', UserRole::Manager)
                        ->where('is_active', true))
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => $get('role') === UserRole::Employee->value)
                    ->helperText('Hanya pengguna aktif dengan peran Atasan yang ditampilkan.'),
                TextInput::make('employee_number')
                    ->label('NIP/Nomor pegawai')
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->label('Telepon')
                    ->tel(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
                Section::make('Preferensi Notifikasi')
                    ->columns(2)
                    ->schema([
                        Toggle::make('notification_preferences.inapp')
                            ->label('In-app (database)')
                            ->default(true),
                        Toggle::make('notification_preferences.email')
                            ->label('Email')
                            ->default(true),
                    ]),
            ]);
    }
}
