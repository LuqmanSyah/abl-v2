<?php

namespace App\Filament\Resources\TrainingRequests;

use App\Enums\TrainingRequestStatus;
use App\Enums\UserRole;
use App\Filament\Resources\TrainingRequests\Pages\ListTrainingRequests;
use App\Models\TrainingRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TrainingRequestResource extends Resource
{
    protected static ?string $model = TrainingRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'pengajuan pelatihan';

    protected static ?string $pluralModelLabel = 'pengajuan pelatihan';

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
        return auth()->user()?->role === UserRole::Employee && (bool) auth()->user()->manager_id;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('user_id')->default(fn (): ?int => auth()->id()),
            Hidden::make('manager_id')->default(fn (): ?int => auth()->user()?->manager_id),
            Hidden::make('status')->default(TrainingRequestStatus::PendingManager->value),
            Hidden::make('requested_at')->default(fn () => now()),
            Select::make('training_id')
                ->label('Pelatihan')
                ->relationship('training', 'name', fn (Builder $query) => $query->where('is_active', true))
                ->searchable()->preload()->required(),
            Textarea::make('reason')->label('Alasan pengajuan')->required()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')->label('Pegawai')->searchable(),
                TextColumn::make('training.name')->label('Pelatihan')->searchable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof TrainingRequestStatus ? $state->label() : (string) $state),
                TextColumn::make('reason')->label('Alasan')->limit(50),
                TextColumn::make('manager_notes')->label('Catatan Atasan')->limit(50)->placeholder('-'),
                TextColumn::make('hr_result')->label('Hasil')->limit(50)->placeholder('-'),
                TextColumn::make('requested_at')->label('Diajukan')->dateTime()->sortable(),
            ])
            ->recordActions([
                Action::make('approve_manager')->label('Setujui')->color('success')
                    ->schema([Textarea::make('notes')->label('Catatan')])
                    ->visible(fn ($record): bool => auth()->user()->role === UserRole::Manager && $record->status === TrainingRequestStatus::PendingManager)
                    ->action(fn ($record, array $data) => $record->approveByManager(auth()->user(), $data['notes'] ?? null)),
                Action::make('reject_manager')->label('Tolak')->color('danger')
                    ->schema([Textarea::make('notes')->label('Alasan')->required()])
                    ->visible(fn ($record): bool => auth()->user()->role === UserRole::Manager && $record->status === TrainingRequestStatus::PendingManager)
                    ->action(fn ($record, array $data) => $record->rejectByManager(auth()->user(), $data['notes'])),
                Action::make('verify_hr')->label('Verifikasi HR')->color('success')->requiresConfirmation()
                    ->visible(fn ($record): bool => auth()->user()->role === UserRole::Hr && $record->status === TrainingRequestStatus::PendingHr)
                    ->action(fn ($record) => $record->verifyByHr(auth()->user())),
                Action::make('complete')->label('Catat Hasil')->color('success')
                    ->schema([Textarea::make('result')->label('Hasil pelatihan')->required()])
                    ->visible(fn ($record): bool => auth()->user()->role === UserRole::Hr && $record->status === TrainingRequestStatus::Approved)
                    ->action(fn ($record, array $data) => $record->complete(auth()->user(), $data['result'])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListTrainingRequests::route('/')];
    }
}
