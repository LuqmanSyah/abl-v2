<?php

namespace App\Filament\Resources;

use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Filament\Resources\PerformanceReviewResource\Pages;
use App\Models\PerformanceReview;
use App\Models\User;
use App\Services\MeritScoreService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PerformanceReviewResource extends RoleAwareResource
{
    protected static ?string $model = PerformanceReview::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem Merit';

    protected static ?string $modelLabel = 'Review Kinerja';

    protected static ?string $pluralModelLabel = 'Review Kinerja';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship(
                    name: 'user',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => static::isRole(UserRole::Manager)
                        ? $query->where('manager_id', Auth::id())
                        : $query,
                )
                ->disabled(fn (): bool => Filament::getCurrentPanel()?->getId() === 'employee')
                ->required()
                ->searchable()
                ->preload()
                ->label('Karyawan'),

            Select::make('reviewer_id')
                ->relationship('reviewer', 'name')
                ->default(fn () => Auth::id())
                ->disabled(fn (): bool => Filament::getCurrentPanel()?->getId() === 'employee'
                    || static::isRole(UserRole::Manager))
                ->dehydrated(fn (): bool => Filament::getCurrentPanel()?->getId() !== 'employee')
                ->required()
                ->searchable()
                ->preload()
                ->label('Reviewer'),

            TextInput::make('period')
                ->disabled(fn (): bool => Filament::getCurrentPanel()?->getId() === 'employee')
                ->required()
                ->maxLength(255)
                ->label('Periode'),

            DatePicker::make('start_date')
                ->disabled(fn (): bool => Filament::getCurrentPanel()?->getId() === 'employee')
                ->required()
                ->label('Tanggal Mulai'),

            DatePicker::make('end_date')
                ->disabled(fn (): bool => Filament::getCurrentPanel()?->getId() === 'employee')
                ->required()
                ->afterOrEqual('start_date')
                ->label('Tanggal Selesai'),

            Repeater::make('reviewKpiDetails')
                ->relationship()
                ->schema(ReviewKpiDetailResource::formComponents())
                ->hiddenOn('create')
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->columns(2)
                ->columnSpanFull()
                ->label('Detail KPI'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Karyawan'),

                TextColumn::make('reviewer.name')
                    ->searchable()
                    ->sortable()
                    ->label('Reviewer'),

                TextColumn::make('period')
                    ->searchable()
                    ->sortable()
                    ->label('Periode'),

                TextColumn::make('start_date')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Mulai'),

                TextColumn::make('end_date')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Selesai'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ReviewStatus $state): string => match ($state) {
                        ReviewStatus::Draft => 'gray',
                        ReviewStatus::Submitted => 'warning',
                        ReviewStatus::Approved => 'success',
                        ReviewStatus::Locked => 'info',
                    })
                    ->label('Status'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(ReviewStatus::class)
                    ->label('Status'),
            ])
            ->actions([
                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (PerformanceReview $record): bool => static::isRole(UserRole::Manager, UserRole::HrAdmin)
                        && $record->status === ReviewStatus::Draft)
                    ->action(fn (PerformanceReview $record) => $record->update([
                        'status' => ReviewStatus::Submitted,
                    ])),
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (PerformanceReview $record): bool => static::isRole(UserRole::HrAdmin, UserRole::Director)
                        && $record->status === ReviewStatus::Submitted)
                    ->action(function (PerformanceReview $record): void {
                        app(MeritScoreService::class)->calculate($record);
                        $record->update(['status' => ReviewStatus::Approved]);
                    }),
                Action::make('lock')
                    ->label('Kunci')
                    ->icon('heroicon-o-lock-closed')
                    ->requiresConfirmation()
                    ->visible(fn (PerformanceReview $record): bool => static::isRole(UserRole::HrAdmin, UserRole::Director)
                        && $record->status === ReviewStatus::Approved)
                    ->action(fn (PerformanceReview $record) => $record->update([
                        'status' => ReviewStatus::Locked,
                    ])),
                Action::make('recalculate')
                    ->label('Hitung Ulang')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->visible(fn (PerformanceReview $record): bool => static::isRole(UserRole::HrAdmin, UserRole::Director)
                        && $record->status === ReviewStatus::Locked)
                    ->action(fn (PerformanceReview $record) => app(MeritScoreService::class)
                        ->calculate($record, force: true)),

                EditAction::make()
                    ->visible(fn (PerformanceReview $record): bool => $record->status === ReviewStatus::Draft
                        && ! static::isRole(UserRole::Director)),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Filament::getCurrentPanel()?->getId() === 'employee') {
            return $query->whereBelongsTo(Auth::user());
        }

        return static::isRole(UserRole::Manager)
            ? $query->where('reviewer_id', Auth::id())
            : $query;
    }

    public static function canCreate(): bool
    {
        return Filament::getCurrentPanel()?->getId() !== 'employee'
            && static::isRole(UserRole::Manager, UserRole::HrAdmin)
            && parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        if (! parent::canEdit($record)
            || ! $record instanceof PerformanceReview
            || $record->status !== ReviewStatus::Draft) {
            return false;
        }

        if (Filament::getCurrentPanel()?->getId() === 'employee') {
            return $record->user_id === Auth::id();
        }

        return static::isRole(UserRole::HrAdmin)
            || (static::isRole(UserRole::Manager) && $record->reviewer_id === Auth::id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPerformanceReviews::route('/'),
            'create' => Pages\CreatePerformanceReview::route('/create'),
            'edit' => Pages\EditPerformanceReview::route('/{record}/edit'),
        ];
    }

    private static function isRole(UserRole ...$roles): bool
    {
        $user = Auth::user();

        return $user instanceof User && in_array($user->role, $roles, true);
    }
}
