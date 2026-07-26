<?php

namespace App\Filament\Resources;

use App\Enums\PromotionStatus;
use App\Filament\Resources\PromotionResource\Pages;
use App\Models\Promotion;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class PromotionResource extends RoleAwareResource
{
    protected static ?string $model = Promotion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rocket-launch';

    protected static string|UnitEnum|null $navigationGroup = 'Pembinaan Karir';

    protected static ?string $modelLabel = 'Promosi';

    protected static ?string $pluralModelLabel = 'Promosi';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->label('Karyawan'),

            Select::make('from_position_id')
                ->relationship('fromPosition', 'title')
                ->required()
                ->searchable()
                ->preload()
                ->label('Posisi Asal'),

            Select::make('to_position_id')
                ->relationship('toPosition', 'title')
                ->required()
                ->different('from_position_id')
                ->searchable()
                ->preload()
                ->label('Posisi Tujuan'),

            Select::make('proposed_by')
                ->relationship('proposer', 'name')
                ->default(fn () => Auth::id())
                ->required()
                ->searchable()
                ->preload()
                ->label('Diusulkan Oleh'),

            TextInput::make('readiness_score')
                ->required()
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->suffix('%')
                ->label('Readiness Score'),

            Select::make('status')
                ->options(PromotionStatus::class)
                ->default(PromotionStatus::Proposed->value)
                ->required()
                ->label('Status'),

            DatePicker::make('effective_date')
                ->label('Tanggal Efektif'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Karyawan'),

                TextColumn::make('fromPosition.title')
                    ->label('Posisi Asal'),

                TextColumn::make('toPosition.title')
                    ->label('Posisi Tujuan'),

                TextColumn::make('readiness_score')
                    ->numeric(2)
                    ->suffix('%')
                    ->sortable()
                    ->label('Readiness'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (PromotionStatus $state): string => match ($state) {
                        PromotionStatus::Proposed => 'warning',
                        PromotionStatus::ApprovedByHr => 'info',
                        PromotionStatus::ApprovedByDirector => 'success',
                        PromotionStatus::Rejected => 'danger',
                        PromotionStatus::Expired => 'gray',
                    })
                    ->label('Status'),

                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->label('Diusulkan'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(PromotionStatus::class)
                    ->label('Status'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromotions::route('/'),
        ];
    }
}
