<?php

namespace App\Filament\Resources\DevelopmentPlans;

use App\Enums\UserRole;
use App\Filament\Resources\DevelopmentPlans\Pages\ListDevelopmentPlans;
use App\Models\DevelopmentPlan;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DevelopmentPlanResource extends Resource
{
    protected static ?string $model = DevelopmentPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Pengembangan';

    protected static ?string $modelLabel = 'rencana pengembangan';

    protected static ?string $pluralModelLabel = 'rencana pengembangan';

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
        return in_array(auth()->user()?->role, [UserRole::Manager, UserRole::Hr], true);
    }

    public static function canEdit(Model $record): bool
    {
        if (! $record instanceof DevelopmentPlan) {
            return false;
        }

        return auth()->user()?->role === UserRole::Hr
            || (auth()->user()?->role === UserRole::Manager && $record->employee->manager_id === auth()->id());
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('employee_id')
                ->label('Pegawai')
                ->relationship('employee', 'name', fn (Builder $query) => $query
                    ->where('role', UserRole::Employee)
                    ->where('is_active', true)
                    ->when(
                        auth()->user()?->role === UserRole::Manager,
                        fn (Builder $query) => $query->where('manager_id', auth()->id()),
                    ))
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('target')->label('Target karier')->required(),
            Textarea::make('current_gap')->label('Gap saat ini')->required(),
            Textarea::make('recommended_action')->label('Tindakan pengembangan')->required(),
            DatePicker::make('review_date')->label('Tanggal tinjau'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')->label('Pegawai')->searchable(),
                TextColumn::make('target')->label('Target')->searchable(),
                TextColumn::make('current_gap')->label('Gap')->limit(50),
                TextColumn::make('recommended_action')->label('Tindakan')->limit(50),
                TextColumn::make('review_date')->label('Tinjau')->date()->sortable(),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (DevelopmentPlan $record): bool => static::canEdit($record)),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListDevelopmentPlans::route('/')];
    }
}
