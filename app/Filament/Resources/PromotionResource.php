<?php

namespace App\Filament\Resources;

use App\Enums\PromotionStatus;
use App\Enums\UserRole;
use App\Filament\Resources\PromotionResource\Pages;
use App\Models\Promotion;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PromotionResource extends RoleAwareResource
{
    protected static ?string $model = Promotion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rocket-launch';

    protected static string|UnitEnum|null $navigationGroup = 'Pembinaan Karir';

    protected static ?string $modelLabel = 'Promosi';

    protected static ?string $pluralModelLabel = 'Promosi';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship(
                    name: 'user',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => $query->where('manager_id', Auth::id()),
                )
                ->required()
                ->searchable()
                ->preload()
                ->label('Karyawan'),

            Select::make('to_position_id')
                ->relationship('toPosition', 'title')
                ->required()
                ->searchable()
                ->preload()
                ->label('Posisi Tujuan'),
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

                TextColumn::make('fromPosition.title')
                    ->label('Posisi Asal'),

                TextColumn::make('toPosition.title')
                    ->label('Posisi Tujuan'),

                TextColumn::make('readiness_score')
                    ->numeric(2)
                    ->suffix('%')
                    ->sortable()
                    ->label('Readiness'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (PromotionStatus $state): string => match ($state) {
                        PromotionStatus::Proposed => 'warning',
                        PromotionStatus::ApprovedByHr => 'info',
                        PromotionStatus::ApprovedByDirector => 'success',
                        PromotionStatus::Rejected => 'danger',
                        PromotionStatus::Expired => 'gray',
                    })
                    ->label('Status'),

                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->label('Diusulkan'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(PromotionStatus::class)
                    ->label('Status'),
            ])
            ->actions([
                Action::make('approve_hr')
                    ->label('Verifikasi HR')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Promotion $record): bool => static::isRole(UserRole::HrAdmin)
                        && $record->status === PromotionStatus::Proposed)
                    ->action(fn (Promotion $record) => $record->update([
                        'status' => PromotionStatus::ApprovedByHr,
                    ])),
                Action::make('approve_director')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->schema([
                        DatePicker::make('effective_date')
                            ->required()
                            ->label('Tanggal Efektif'),
                    ])
                    ->visible(fn (Promotion $record): bool => static::isRole(UserRole::Director)
                        && $record->status === PromotionStatus::ApprovedByHr)
                    ->action(fn (Promotion $record, array $data) => $record->update([
                        'status' => PromotionStatus::ApprovedByDirector,
                        'effective_date' => $data['effective_date'],
                    ])),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Promotion $record): bool => (static::isRole(UserRole::HrAdmin)
                        && $record->status === PromotionStatus::Proposed)
                        || (static::isRole(UserRole::Director)
                            && $record->status === PromotionStatus::ApprovedByHr))
                    ->action(fn (Promotion $record) => $record->update([
                        'status' => PromotionStatus::Rejected,
                    ])),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return static::isRole(UserRole::Manager)
            ? $query->where('proposed_by', Auth::id())
            : $query;
    }

    public static function canCreate(): bool
    {
        return static::isRole(UserRole::Manager) && parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromotions::route('/'),
        ];
    }

    private static function isRole(UserRole ...$roles): bool
    {
        $user = Auth::user();

        return $user instanceof User && in_array($user->role, $roles, true);
    }
}
