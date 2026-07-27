<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchOfficeResource\Pages;
use App\Models\BranchOffice;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class BranchOfficeResource extends RoleAwareResource
{
    protected static ?string $model = BranchOffice::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $modelLabel = 'Kantor Cabang';

    protected static ?string $pluralModelLabel = 'Kantor Cabang';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->label('Nama'),

            TextInput::make('code')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->label('Kode'),

            TextInput::make('latitude')
                ->required()
                ->numeric()
                ->minValue(-90)
                ->maxValue(90)
                ->step(0.0000001)
                ->live(debounce: 500)
                ->label('Latitude'),

            TextInput::make('longitude')
                ->required()
                ->numeric()
                ->minValue(-180)
                ->maxValue(180)
                ->step(0.0000001)
                ->live(debounce: 500)
                ->label('Longitude'),

            TextInput::make('allowed_radius_meters')
                ->required()
                ->integer()
                ->minValue(1)
                ->suffix('meter')
                ->label('Radius Absensi'),

            View::make('filament.forms.components.map-picker')
                ->viewData([
                    'latitudeStatePath' => 'data.latitude',
                    'longitudeStatePath' => 'data.longitude',
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama'),

                TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->label('Kode'),

                TextColumn::make('latitude')
                    ->label('Latitude'),

                TextColumn::make('longitude')
                    ->label('Longitude'),

                TextColumn::make('allowed_radius_meters')
                    ->numeric()
                    ->suffix(' m')
                    ->sortable()
                    ->label('Radius'),
            ])
            ->defaultSort('name')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranchOffices::route('/'),
        ];
    }
}
