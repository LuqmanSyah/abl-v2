<?php

namespace App\Filament\Resources\KpiIndicators;

use App\Enums\UserRole;
use App\Filament\Resources\KpiIndicators\Pages\CreateKpiIndicator;
use App\Filament\Resources\KpiIndicators\Pages\EditKpiIndicator;
use App\Filament\Resources\KpiIndicators\Pages\ListKpiIndicators;
use App\Filament\Resources\KpiIndicators\Schemas\KpiIndicatorForm;
use App\Filament\Resources\KpiIndicators\Tables\KpiIndicatorsTable;
use App\Models\KpiIndicator;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class KpiIndicatorResource extends Resource
{
    protected static ?string $model = KpiIndicator::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Kinerja';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'indikator KPI';

    protected static ?string $pluralModelLabel = 'indikator KPI';

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
        return static::canViewAny()
            && $record instanceof KpiIndicator
            && $record->reviewPeriod?->hasPublishedMeritResults() === false;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function form(Schema $schema): Schema
    {
        return KpiIndicatorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KpiIndicatorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKpiIndicators::route('/'),
            'create' => CreateKpiIndicator::route('/create'),
            'edit' => EditKpiIndicator::route('/{record}/edit'),
        ];
    }
}
