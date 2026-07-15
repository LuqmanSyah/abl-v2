<?php

namespace App\Filament\Resources\ReviewPeriods;

use App\Enums\UserRole;
use App\Filament\Resources\ReviewPeriods\Pages\CreateReviewPeriod;
use App\Filament\Resources\ReviewPeriods\Pages\EditReviewPeriod;
use App\Filament\Resources\ReviewPeriods\Pages\ListReviewPeriods;
use App\Filament\Resources\ReviewPeriods\Schemas\ReviewPeriodForm;
use App\Filament\Resources\ReviewPeriods\Tables\ReviewPeriodsTable;
use App\Models\ReviewPeriod;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ReviewPeriodResource extends Resource
{
    protected static ?string $model = ReviewPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Kinerja';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'periode penilaian';

    protected static ?string $pluralModelLabel = 'periode penilaian';

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
        return static::canViewAny() && $record instanceof ReviewPeriod && ! $record->hasPublishedMeritResults();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ReviewPeriodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReviewPeriodsTable::configure($table);
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
            'index' => ListReviewPeriods::route('/'),
            'create' => CreateReviewPeriod::route('/create'),
            'edit' => EditReviewPeriod::route('/{record}/edit'),
        ];
    }
}
