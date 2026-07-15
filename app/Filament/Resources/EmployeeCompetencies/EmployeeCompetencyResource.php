<?php

namespace App\Filament\Resources\EmployeeCompetencies;

use App\Enums\UserRole;
use App\Filament\Resources\EmployeeCompetencies\Pages\ListEmployeeCompetencies;
use App\Models\EmployeeCompetency;
use BackedEnum;
use Filament\Actions\DeleteAction;
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

class EmployeeCompetencyResource extends Resource
{
    protected static ?string $model = EmployeeCompetency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|UnitEnum|null $navigationGroup = 'Pengembangan';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'kompetensi pegawai';

    protected static ?string $pluralModelLabel = 'kompetensi pegawai';

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
        return auth()->user()?->role === UserRole::Hr;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canCreate();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Pegawai')
                ->relationship('employee', 'name', fn (Builder $query) => $query->where('role', UserRole::Employee))
                ->searchable()->preload()->required(),
            Select::make('competency_id')->label('Kompetensi')->relationship('competency', 'name')->searchable()->preload()->required(),
            TextInput::make('level')->label('Level saat ini')->numeric()->minValue(1)->maxValue(5)->required(),
            DatePicker::make('assessed_at')->label('Tanggal penilaian')->default(today())->required(),
            Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')->label('Pegawai')->searchable(),
                TextColumn::make('competency.name')->label('Kompetensi')->searchable(),
                TextColumn::make('level')->label('Level')->sortable(),
                TextColumn::make('assessed_at')->label('Dinilai pada')->date()->sortable(),
                TextColumn::make('notes')->label('Catatan')->limit(60),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (): bool => auth()->user()->role === UserRole::Hr),
                DeleteAction::make()->visible(fn (): bool => auth()->user()->role === UserRole::Hr),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListEmployeeCompetencies::route('/')];
    }
}
