<?php

namespace App\Filament\Resources;

use App\Enums\ReviewStatus;
use App\Filament\Resources\PerformanceReviewResource\Pages;
use App\Models\PerformanceReview;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PerformanceReviewResource extends Resource
{
    protected static ?string $model = PerformanceReview::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem Merit';

    protected static ?string $modelLabel = 'Review Kinerja';

    protected static ?string $pluralModelLabel = 'Review Kinerja';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->relationship('user', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->label('Karyawan'),

            Select::make('reviewer_id')
                ->relationship('reviewer', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->label('Reviewer'),

            TextInput::make('period')
                ->required()
                ->maxLength(255)
                ->label('Periode'),

            DatePicker::make('start_date')
                ->required()
                ->label('Tanggal Mulai'),

            DatePicker::make('end_date')
                ->required()
                ->afterOrEqual('start_date')
                ->label('Tanggal Selesai'),

            Repeater::make('reviewKpiDetails')
                ->relationship()
                ->schema(ReviewKpiDetailResource::formComponents())
                ->hiddenOn('create')
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->columns(2)
                ->columnSpanFull()
                ->label('Detail KPI'),
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

                TextColumn::make('reviewer.name')
                    ->searchable()
                    ->sortable()
                    ->label('Reviewer'),

                TextColumn::make('period')
                    ->searchable()
                    ->sortable()
                    ->label('Periode'),

                TextColumn::make('start_date')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Mulai'),

                TextColumn::make('end_date')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Selesai'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ReviewStatus $state): string => match ($state) {
                        ReviewStatus::Draft => 'gray',
                        ReviewStatus::Submitted => 'warning',
                        ReviewStatus::Approved => 'success',
                        ReviewStatus::Locked => 'info',
                    })
                    ->label('Status'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(ReviewStatus::class)
                    ->label('Status'),
            ])
            ->actions([
                Action::make('submit')
                    ->label('Submit')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (PerformanceReview $record): bool => $record->status === ReviewStatus::Draft)
                    ->action(fn (PerformanceReview $record) => $record->update([
                        'status' => ReviewStatus::Submitted,
                    ])),

                EditAction::make()
                    ->visible(fn (PerformanceReview $record): bool => $record->status === ReviewStatus::Draft),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPerformanceReviews::route('/'),
            'create' => Pages\CreatePerformanceReview::route('/create'),
            'edit' => Pages\EditPerformanceReview::route('/{record}/edit'),
        ];
    }
}
