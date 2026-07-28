<?php

namespace App\Filament\Resources\DutyLocations;

use App\Enums\UserRole;
use App\Filament\Resources\DutyLocations\Pages\CreateDutyLocation;
use App\Filament\Resources\DutyLocations\Pages\EditDutyLocation;
use App\Filament\Resources\DutyLocations\Pages\ListDutyLocations;
use App\Filament\Resources\DutyLocations\Schemas\DutyLocationForm;
use App\Filament\Resources\DutyLocations\Tables\DutyLocationsTable;
use App\Models\DutyLocation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DutyLocationResource extends Resource
{
    protected static ?string $model = DutyLocation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'lokasi dinas';

    protected static ?string $pluralModelLabel = 'lokasi dinas';

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
        return DutyLocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DutyLocationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDutyLocations::route('/'),
            'create' => CreateDutyLocation::route('/create'),
            'edit' => EditDutyLocation::route('/{record}/edit'),
        ];
    }
}
