<?php

namespace App\Filament\Resources\PerformanceReviews;

use App\Enums\UserRole;
use App\Filament\Resources\PerformanceReviews\Pages\CreatePerformanceReview;
use App\Filament\Resources\PerformanceReviews\Pages\ListPerformanceReviews;
use App\Filament\Resources\PerformanceReviews\Pages\ViewPerformanceReview;
use App\Filament\Resources\PerformanceReviews\Schemas\PerformanceReviewForm;
use App\Filament\Resources\PerformanceReviews\Schemas\PerformanceReviewInfolist;
use App\Filament\Resources\PerformanceReviews\Tables\PerformanceReviewsTable;
use App\Models\PerformanceReview;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PerformanceReviewResource extends Resource
{
    protected static ?string $model = PerformanceReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Kinerja';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'umpan balik kinerja';

    protected static ?string $pluralModelLabel = 'umpan balik kinerja';

    public static function getNavigationLabel(): string
    {
        return 'Umpan Balik Kinerja';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()->role === UserRole::Hr ? $query : $query->where('reviewer_id', auth()->id());
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
        return in_array(auth()->user()?->role, [UserRole::Employee, UserRole::Manager], true);
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return PerformanceReviewForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PerformanceReviewInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PerformanceReviewsTable::configure($table);
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
            'index' => ListPerformanceReviews::route('/'),
            'create' => CreatePerformanceReview::route('/create'),
            'view' => ViewPerformanceReview::route('/{record}'),
        ];
    }
}
