<?php

namespace App\Filament\Resources\MeritResults;

use App\Filament\Resources\MeritResults\Pages\ListMeritResults;
use App\Filament\Resources\MeritResults\Pages\ViewMeritResult;
use App\Models\MeritResult;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MeritResultResource extends Resource
{
    protected static ?string $model = MeritResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = 'Kinerja';

    protected static ?string $modelLabel = 'hasil merit';

    protected static ?string $pluralModelLabel = 'hasil merit';

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
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('reviewPeriod.name')->label('Periode'),
            TextEntry::make('employee.name')->label('Pegawai'),
            TextEntry::make('kpi_score')->label('KPI (80%)')->numeric(decimalPlaces: 2),
            TextEntry::make('attendance_score')->label('Kehadiran (20%)')->numeric(decimalPlaces: 2),
            TextEntry::make('total_score')->label('Nilai akhir')->numeric(decimalPlaces: 2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reviewPeriod.name')->label('Periode')->searchable(),
                TextColumn::make('employee.name')->label('Pegawai')->searchable(),
                TextColumn::make('kpi_score')->label('KPI')->numeric(decimalPlaces: 2)->sortable(),
                TextColumn::make('attendance_score')->label('Kehadiran')->numeric(decimalPlaces: 2)->sortable(),
                TextColumn::make('total_score')->label('Nilai akhir')->numeric(decimalPlaces: 2)->sortable(),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('total_score', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeritResults::route('/'),
            'view' => ViewMeritResult::route('/{record}'),
        ];
    }
}
