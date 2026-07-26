<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionResource\Pages;
use App\Models\Position;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PositionResource extends RoleAwareResource
{
    protected static ?string $model = Position::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Jabatan';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('department_id')
                ->relationship('department', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->label('Departemen'),
            TextInput::make('title')->required()->maxLength(255)->label('Nama Jabatan'),
            TextInput::make('level')->required()->integer()->minValue(1)->maxValue(255)->label('Level'),
            Repeater::make('positionSkills')
                ->relationship()
                ->defaultItems(0)
                ->schema([
                    Select::make('skill_id')
                        ->relationship('skill', 'name')
                        ->required()
                        ->distinct()
                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                        ->searchable()
                        ->preload()
                        ->label('Keahlian'),
                    TextInput::make('min_required_level')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(255)
                        ->label('Level Minimum'),
                ])
                ->columns(2)
                ->columnSpanFull()
                ->label('Persyaratan Keahlian'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->label('Jabatan'),
                TextColumn::make('department.name')->searchable()->sortable()->label('Departemen'),
                TextColumn::make('level')->numeric()->sortable()->label('Level'),
            ])
            ->defaultSort('title')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListPositions::route('/')];
    }
}
