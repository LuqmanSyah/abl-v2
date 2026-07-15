<?php

namespace App\Filament\Resources\EmployeeKpis;

use App\Enums\UserRole;
use App\Filament\Resources\EmployeeKpis\Pages\CreateEmployeeKpi;
use App\Filament\Resources\EmployeeKpis\Pages\EditEmployeeKpi;
use App\Filament\Resources\EmployeeKpis\Pages\ListEmployeeKpis;
use App\Filament\Resources\EmployeeKpis\Pages\ViewEmployeeKpi;
use App\Filament\Resources\EmployeeKpis\Schemas\EmployeeKpiForm;
use App\Filament\Resources\EmployeeKpis\Schemas\EmployeeKpiInfolist;
use App\Filament\Resources\EmployeeKpis\Tables\EmployeeKpisTable;
use App\Models\EmployeeKpi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EmployeeKpiResource extends Resource
{
    protected static ?string $model = EmployeeKpi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'KPI pegawai';

    protected static ?string $pluralModelLabel = 'KPI pegawai';

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
        return auth()->user()?->role === UserRole::Manager;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->role === UserRole::Manager && $record->manager_id === auth()->id();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function form(Schema $schema): Schema
    {
        return EmployeeKpiForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeKpiInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeKpisTable::configure($table);
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
            'index' => ListEmployeeKpis::route('/'),
            'create' => CreateEmployeeKpi::route('/create'),
            'view' => ViewEmployeeKpi::route('/{record}'),
            'edit' => EditEmployeeKpi::route('/{record}/edit'),
        ];
    }
}
