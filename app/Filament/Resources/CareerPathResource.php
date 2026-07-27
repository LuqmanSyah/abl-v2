<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CareerPathResource\Pages;
use App\Models\CareerPath;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CareerPathResource extends RoleAwareResource
{
    protected static ?string $model = CareerPath::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static string|UnitEnum|null $navigationGroup = 'Pembinaan Karir';

    protected static ?string $modelLabel = 'Jalur Karir';

    protected static ?string $pluralModelLabel = 'Jalur Karir';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('current_position_id')
                ->relationship('currentPosition', 'title')
                ->required()
                ->searchable()
                ->preload()
                ->label('Posisi Saat Ini'),

            Select::make('next_position_id')
                ->relationship('nextPosition', 'title')
                ->required()
                ->different('current_position_id')
                ->searchable()
                ->preload()
                ->label('Posisi Berikutnya'),

            TextInput::make('min_experience_months')
                ->required()
                ->integer()
                ->minValue(0)
                ->suffix('bulan')
                ->label('Minimum Pengalaman'),

            Select::make('min_merit_grade')
                ->options(['A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D'])
                ->required()
                ->label('Minimum Grade Merit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('currentPosition.title')
                    ->searchable()
                    ->sortable()
                    ->label('Posisi Saat Ini'),

                TextColumn::make('nextPosition.title')
                    ->searchable()
                    ->sortable()
                    ->label('Posisi Berikutnya'),

                TextColumn::make('min_experience_months')
                    ->numeric()
                    ->suffix(' bulan')
                    ->label('Pengalaman Minimum'),

                TextColumn::make('min_merit_grade')
                    ->badge()
                    ->label('Grade Minimum'),
            ])
            ->defaultSort('current_position_id')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return Filament::getCurrentPanel()?->getId() === 'employee'
            ? $query->where('current_position_id', Auth::user()?->position_id)
            : $query;
    }

    public static function canCreate(): bool
    {
        return Filament::getCurrentPanel()?->getId() !== 'employee' && parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        return Filament::getCurrentPanel()?->getId() !== 'employee' && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return Filament::getCurrentPanel()?->getId() !== 'employee' && parent::canDelete($record);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCareerPaths::route('/'),
        ];
    }
}
