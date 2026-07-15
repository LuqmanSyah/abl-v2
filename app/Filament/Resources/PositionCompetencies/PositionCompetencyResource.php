<?php

namespace App\Filament\Resources\PositionCompetencies;

use App\Enums\UserRole;
use App\Filament\Resources\PositionCompetencies\Pages\ListPositionCompetencies;
use App\Models\PositionCompetency;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PositionCompetencyResource extends Resource
{
    protected static ?string $model = PositionCompetency::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'standar kompetensi jabatan';

    protected static ?string $pluralModelLabel = 'standar kompetensi jabatan';

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
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('position_id')->label('Jabatan')->relationship('position', 'name')->searchable()->preload()->required(),
            Select::make('competency_id')->label('Kompetensi')->relationship('competency', 'name')->searchable()->preload()->required(),
            TextInput::make('required_level')->label('Level wajib')->numeric()->minValue(1)->maxValue(5)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('position.name')->label('Jabatan')->searchable()->sortable(),
                TextColumn::make('competency.name')->label('Kompetensi')->searchable(),
                TextColumn::make('required_level')->label('Level wajib')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListPositionCompetencies::route('/')];
    }
}
