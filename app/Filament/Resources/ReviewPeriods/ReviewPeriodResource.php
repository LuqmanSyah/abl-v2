<?php

namespace App\Filament\Resources\ReviewPeriods;

use App\Enums\UserRole;
use App\Filament\Resources\ReviewPeriods\Pages\CreateReviewPeriod;
use App\Filament\Resources\ReviewPeriods\Pages\EditReviewPeriod;
use App\Filament\Resources\ReviewPeriods\Pages\ListReviewPeriods;
use App\Models\ReviewPeriod;
use App\Services\MeritCalculator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ReviewPeriodResource extends Resource
{
    protected static ?string $model = ReviewPeriod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Kinerja';

    protected static ?string $modelLabel = 'periode';

    protected static ?string $pluralModelLabel = 'periode';

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
        return static::canViewAny() && $record instanceof ReviewPeriod && ! $record->published_at;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required(),
            DatePicker::make('starts_at')->label('Mulai')->required(),
            DatePicker::make('ends_at')->label('Selesai')->afterOrEqual('starts_at')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('starts_at')->label('Mulai')->date()->sortable(),
                TextColumn::make('ends_at')->label('Selesai')->date()->sortable(),
                IconColumn::make('published_at')
                    ->label('Terpublikasi')
                    ->getStateUsing(fn (ReviewPeriod $record): bool => (bool) $record->published_at)
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (ReviewPeriod $record): bool => static::canEdit($record)),
                Action::make('publish')
                    ->label('Hitung dan Publikasikan')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ReviewPeriod $record): bool => ! $record->published_at)
                    ->action(fn (ReviewPeriod $record) => app(MeritCalculator::class)->publish($record, auth()->user())),
            ])
            ->defaultSort('starts_at', 'desc');
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
