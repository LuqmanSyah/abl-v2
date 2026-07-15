<?php

namespace App\Filament\Resources\DutyTrips;

use App\Enums\DutyTripStatus;
use App\Enums\UserRole;
use App\Filament\Resources\DutyTrips\Pages\CreateDutyTrip;
use App\Filament\Resources\DutyTrips\Pages\EditDutyTrip;
use App\Filament\Resources\DutyTrips\Pages\ListDutyTrips;
use App\Filament\Resources\DutyTrips\Pages\ViewDutyTrip;
use App\Filament\Resources\DutyTrips\Schemas\DutyTripForm;
use App\Filament\Resources\DutyTrips\Schemas\DutyTripInfolist;
use App\Filament\Resources\DutyTrips\Tables\DutyTripsTable;
use App\Models\DutyTrip;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DutyTripResource extends Resource
{
    protected static ?string $model = DutyTrip::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'dinas';

    protected static ?string $pluralModelLabel = 'pengajuan dinas';

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
        return auth()->user()?->role === UserRole::Employee;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->role === UserRole::Employee
            && $record->employee_id === auth()->id()
            && $record->status === DutyTripStatus::Pending;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function form(Schema $schema): Schema
    {
        return DutyTripForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DutyTripInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DutyTripsTable::configure($table);
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
            'index' => ListDutyTrips::route('/'),
            'create' => CreateDutyTrip::route('/create'),
            'view' => ViewDutyTrip::route('/{record}'),
            'edit' => EditDutyTrip::route('/{record}/edit'),
        ];
    }
}
