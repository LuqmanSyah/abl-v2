<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchOfficeResource\Pages;
use App\Models\BranchOffice;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class BranchOfficeResource extends Resource
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

            TextEntry::make('map_preview')
                ->state(fn (Get $get): HtmlString => static::mapPreview(
                    $get('latitude'),
                    $get('longitude'),
                ))
                ->columnSpanFull()
                ->label('Peta Lokasi'),
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

    private static function mapPreview(mixed $latitude, mixed $longitude): HtmlString
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return new HtmlString('Isi latitude dan longitude untuk menampilkan peta.');
        }

        $key = config('services.google_maps.key');

        if (blank($key)) {
            return new HtmlString('GOOGLE_MAPS_API_KEY belum dikonfigurasi.');
        }

        $url = 'https://www.google.com/maps/embed/v1/place?'.http_build_query([
            'key' => $key,
            'q' => "{$latitude},{$longitude}",
            'zoom' => 16,
        ]);

        return new HtmlString(
            '<iframe title="Peta lokasi kantor" src="'.e($url).'" style="width:100%;height:320px;border:0;border-radius:0.75rem" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
        );
    }
}
