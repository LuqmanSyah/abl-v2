<?php

namespace App\Filament\Resources\CareerGoals;

use App\Enums\UserRole;
use App\Filament\Resources\CareerGoals\Pages\ListCareerGoals;
use App\Models\CareerGoal;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CareerGoalResource extends Resource
{
    protected static ?string $model = CareerGoal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'target karier';

    protected static ?string $pluralModelLabel = 'target karier';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === UserRole::Employee
            && ! CareerGoal::where('user_id', auth()->id())->exists();
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->role === UserRole::Employee && $record->user_id === auth()->id();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')->default(fn (): ?int => auth()->id()),
            Select::make('target_position_id')
                ->label('Jabatan tujuan')
                ->relationship('targetPosition', 'name', fn (Builder $query) => $query->where('level', '>', auth()->user()->position?->level ?? PHP_INT_MAX))
                ->searchable()->preload()->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')->label('Pegawai')->searchable(),
                TextColumn::make('employee.position.name')->label('Jabatan saat ini'),
                TextColumn::make('targetPosition.name')->label('Jabatan tujuan')->searchable(),
                TextColumn::make('gap_summary')->label('Gap dan rekomendasi')->wrap(),
            ])
            ->recordActions([
                EditAction::make()->visible(fn ($record): bool => static::canEdit($record)),
                DeleteAction::make()->visible(fn ($record): bool => static::canDelete($record)),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCareerGoals::route('/')];
    }
}
