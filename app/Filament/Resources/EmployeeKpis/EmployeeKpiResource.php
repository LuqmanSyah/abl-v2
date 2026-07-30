<?php

namespace App\Filament\Resources\EmployeeKpis;

use App\Enums\UserRole;
use App\Filament\Resources\EmployeeKpis\Pages\CreateEmployeeKpi;
use App\Filament\Resources\EmployeeKpis\Pages\EditEmployeeKpi;
use App\Filament\Resources\EmployeeKpis\Pages\ListEmployeeKpis;
use App\Filament\Resources\EmployeeKpis\Pages\ViewEmployeeKpi;
use App\Models\EmployeeKpi;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class EmployeeKpiResource extends Resource
{
    protected static ?string $model = EmployeeKpi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Kinerja';

    protected static ?string $modelLabel = 'KPI';

    protected static ?string $pluralModelLabel = 'KPI';

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
        return auth()->user()?->role === UserRole::Manager
            && $record instanceof EmployeeKpi
            && $record->manager_id === auth()->id()
            && ! $record->reviewPeriod->published_at;
    }

    public static function canDelete(Model $record): bool
    {
        return static::canEdit($record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('review_period_id')
                ->label('Periode')
                ->relationship('reviewPeriod', 'name', fn (Builder $query) => $query->whereNull('published_at'))
                ->required(),
            Select::make('employee_id')
                ->label('Pegawai')
                ->relationship('employee', 'name', fn (Builder $query) => $query
                    ->where('role', UserRole::Employee)
                    ->where('manager_id', auth()->id())
                    ->where('is_active', true))
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('name')->label('Nama KPI')->required(),
            TextInput::make('target')->numeric()->minValue(0.01)->required(),
            TextInput::make('achievement')->label('Capaian')->numeric()->minValue(0)->default(0)->required(),
            Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('reviewPeriod.name')->label('Periode'),
            TextEntry::make('employee.name')->label('Pegawai'),
            TextEntry::make('name')->label('KPI'),
            TextEntry::make('target')->numeric(decimalPlaces: 2),
            TextEntry::make('achievement')->label('Capaian')->numeric(decimalPlaces: 2),
            TextEntry::make('notes')->label('Catatan')->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reviewPeriod.name')->label('Periode')->searchable(),
                TextColumn::make('employee.name')->label('Pegawai')->searchable(),
                TextColumn::make('name')->label('KPI')->searchable(),
                TextColumn::make('target')->numeric(decimalPlaces: 2),
                TextColumn::make('achievement')->label('Capaian')->numeric(decimalPlaces: 2),
                TextColumn::make('percentage')
                    ->label('Persentase')
                    ->getStateUsing(fn (EmployeeKpi $record): string => number_format(
                        min((float) $record->achievement / (float) $record->target, 1.2) * 100,
                        1,
                    ).'%'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (EmployeeKpi $record): bool => static::canEdit($record)),
            ])
            ->defaultSort('created_at', 'desc');
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
