<?php

namespace App\Filament\Resources\MeritResults;

use App\Enums\UserRole;
use App\Filament\Resources\MeritResults\Pages\ListMeritResults;
use App\Filament\Resources\MeritResults\Pages\ViewMeritResult;
use App\Filament\Resources\MeritResults\Schemas\MeritResultInfolist;
use App\Filament\Resources\MeritResults\Tables\MeritResultsTable;
use App\Models\MeritResult;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MeritResultResource extends Resource
{
    protected static ?string $model = MeritResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = 'Kinerja';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'hasil merit';

    protected static ?string $pluralModelLabel = 'hasil merit';

    public static function getNavigationLabel(): string
    {
        return match (auth()->user()?->role) {
            UserRole::Employee => 'Hasil Merit',
            UserRole::Manager => 'Verifikasi Merit',
            UserRole::Hr => 'Publikasi Merit',
            default => 'Hasil Merit',
        };
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canView(Model $record): bool
    {
        return static::getEloquentQuery()->whereKey($record)->exists();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return MeritResultInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeritResultsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeritResults::route('/'),
            'view' => ViewMeritResult::route('/{record}'),
        ];
    }
}
