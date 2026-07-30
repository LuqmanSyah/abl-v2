<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Organisasi';

    protected static ?string $modelLabel = 'pegawai';

    protected static ?string $pluralModelLabel = 'pegawai';

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === UserRole::Hr;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required(),
            TextInput::make('email')->email()->unique(ignoreRecord: true)->required(),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->rule(Password::min(8))
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state)),
            Select::make('role')
                ->options(UserRole::options())
                ->default(UserRole::Employee->value)
                ->live()
                ->required(),
            Select::make('unit_id')->label('Unit')->relationship('unit', 'name')->searchable()->preload(),
            Select::make('position_id')->label('Jabatan')->relationship('position', 'name')->searchable()->preload(),
            Select::make('manager_id')
                ->label('Atasan')
                ->relationship('manager', 'name', fn (Builder $query) => $query
                    ->where('role', UserRole::Manager)
                    ->where('is_active', true))
                ->visible(fn (Get $get): bool => $get('role') === UserRole::Employee->value)
                ->searchable()
                ->preload(),
            TextInput::make('employee_number')->label('Nomor pegawai')->unique(ignoreRecord: true),
            Toggle::make('is_active')->label('Aktif')->default(true)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof UserRole ? $state->label() : (string) $state),
                TextColumn::make('unit.name')->label('Unit'),
                TextColumn::make('position.name')->label('Jabatan'),
                TextColumn::make('manager.name')->label('Atasan'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('unit')->relationship('unit', 'name'),
                SelectFilter::make('position')->relationship('position', 'name'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
