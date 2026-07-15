<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

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
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('role')
                    ->label('Peran')
                    ->options(UserRole::options())
                    ->default(UserRole::Employee->value)
                    ->required(),
                Select::make('unit_id')
                    ->label('Unit kerja')
                    ->relationship('unit', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('position_id')
                    ->label('Jabatan')
                    ->relationship('position', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('manager_id')
                    ->label('Atasan')
                    ->relationship('manager', 'name')
                    ->searchable()
                    ->preload(),
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
            ]);
    }
}
