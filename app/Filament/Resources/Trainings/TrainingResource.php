<?php

namespace App\Filament\Resources\Trainings;

use App\Enums\UserRole;
use App\Filament\Resources\Trainings\Pages\ListTrainings;
use App\Models\Training;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TrainingResource extends Resource
{
    protected static ?string $model = Training::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'pelatihan';

    protected static ?string $pluralModelLabel = 'katalog pelatihan';

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return auth()->user()?->role === UserRole::Hr ? $query : $query->where('is_active', true);
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
            TextInput::make('name')->label('Nama')->required(),
            TextInput::make('provider')->label('Penyelenggara'),
            Select::make('type')->label('Jenis')->options(['internal' => 'Internal', 'external' => 'Eksternal'])->required(),
            Select::make('competency_id')->label('Kompetensi terkait')->relationship('competency', 'name')->searchable()->preload(),
            DateTimePicker::make('starts_at')->label('Mulai')->native(false),
            DateTimePicker::make('ends_at')->label('Selesai')->native(false)->after('starts_at'),
            Toggle::make('is_active')->label('Aktif')->default(true),
            Textarea::make('description')->label('Deskripsi')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Pelatihan')->searchable()->sortable(),
                TextColumn::make('competency.name')->label('Kompetensi')->placeholder('-'),
                TextColumn::make('provider')->label('Penyelenggara')->placeholder('-'),
                TextColumn::make('type')->label('Jenis')->badge(),
                TextColumn::make('starts_at')->label('Mulai')->dateTime()->placeholder('-'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([
                EditAction::make()->visible(fn (): bool => auth()->user()->role === UserRole::Hr),
                DeleteAction::make()->visible(fn (): bool => auth()->user()->role === UserRole::Hr),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListTrainings::route('/')];
    }
}
