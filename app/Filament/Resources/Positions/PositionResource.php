<?php

namespace App\Filament\Resources\Positions;

use App\Enums\UserRole;
use App\Filament\Resources\Positions\Pages\CreatePosition;
use App\Filament\Resources\Positions\Pages\EditPosition;
use App\Filament\Resources\Positions\Pages\ListPositions;
use App\Models\Position;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Organisasi';

    protected static ?string $modelLabel = 'jabatan';

    protected static ?string $pluralModelLabel = 'jabatan';

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
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('unit_id')->label('Unit')->relationship('unit', 'name')->required(),
            TextInput::make('name')->label('Nama')->required(),
            TextInput::make('level')->numeric()->minValue(1)->default(1)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('unit.name')->label('Unit')->searchable(),
                TextColumn::make('level')->label('Level')->sortable(),
                TextColumn::make('users_count')->label('Pegawai')->counts('users'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPositions::route('/'),
            'create' => CreatePosition::route('/create'),
            'edit' => EditPosition::route('/{record}/edit'),
        ];
    }
}
