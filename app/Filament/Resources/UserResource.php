<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class UserResource extends RoleAwareResource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Administrasi Sistem';

    protected static ?string $modelLabel = 'Pengguna';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nip')->required()->maxLength(255)->unique(ignoreRecord: true)->label('NIP'),
            TextInput::make('name')->required()->maxLength(255)->label('Nama'),
            TextInput::make('email')->required()->email()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('password')
                ->password()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->maxLength(255)
                ->label('Kata Sandi'),
            Select::make('position_id')->relationship('position', 'title')->required()->searchable()->preload()->label('Jabatan'),
            Select::make('work_schedule_id')->relationship('workSchedule', 'name')->required()->searchable()->preload()->label('Jadwal Kerja'),
            Select::make('branch_office_id')->relationship('branchOffice', 'name')->required()->searchable()->preload()->label('Kantor Cabang'),
            Select::make('manager_id')
                ->relationship(
                    'manager',
                    'name',
                    modifyQueryUsing: fn (Builder $query) => $query
                        ->where('role', UserRole::Manager)
                        ->where('status', true),
                )
                ->required(fn (Get $get): bool => in_array(
                    $get('role'),
                    [UserRole::Employee, UserRole::Employee->value],
                    true,
                ))
                ->searchable()
                ->preload()
                ->label('Atasan Langsung'),
            DatePicker::make('join_date')->required()->label('Tanggal Bergabung'),
            Select::make('role')->options(UserRole::class)->required()->live()->label('Peran'),
            Toggle::make('status')->default(true)->required()->label('Aktif'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nip')->searchable()->sortable()->label('NIP'),
                TextColumn::make('name')->searchable()->sortable()->label('Nama'),
                TextColumn::make('email')->searchable(),
                TextColumn::make('position.title')->sortable()->label('Jabatan'),
                TextColumn::make('role')->badge()->label('Peran'),
                IconColumn::make('status')->boolean()->label('Aktif'),
            ])
            ->defaultSort('name')
            ->actions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListUsers::route('/')];
    }
}
