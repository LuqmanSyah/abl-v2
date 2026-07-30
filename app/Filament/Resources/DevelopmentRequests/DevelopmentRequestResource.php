<?php

namespace App\Filament\Resources\DevelopmentRequests;

use App\Enums\UserRole;
use App\Filament\Resources\DevelopmentRequests\Pages\ListDevelopmentRequests;
use App\Models\DevelopmentRequest;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class DevelopmentRequestResource extends Resource
{
    protected static ?string $model = DevelopmentRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Pengembangan';

    protected static ?string $modelLabel = 'pengajuan pengembangan';

    protected static ?string $pluralModelLabel = 'pengajuan pengembangan';

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
        return auth()->user()?->role === UserRole::Employee && (bool) auth()->user()?->manager_id;
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
            Hidden::make('employee_id')->default(fn (): ?int => auth()->id()),
            Hidden::make('manager_id')->default(fn (): ?int => auth()->user()?->manager_id),
            Select::make('type')
                ->label('Jenis')
                ->options([
                    DevelopmentRequest::TYPE_TRAINING => 'Pelatihan',
                    DevelopmentRequest::TYPE_MENTORING => 'Mentoring',
                ])
                ->required(),
            TextInput::make('title')->label('Judul')->required(),
            Textarea::make('reason')->label('Alasan')->required()->columnSpanFull(),
            DateTimePicker::make('scheduled_at')->label('Jadwal usulan')->seconds(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')->label('Pegawai')->searchable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->formatStateUsing(fn (string $state): string => $state === DevelopmentRequest::TYPE_TRAINING ? 'Pelatihan' : 'Mentoring'),
                TextColumn::make('title')->label('Judul')->searchable(),
                TextColumn::make('scheduled_at')->label('Jadwal')->dateTime()->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        DevelopmentRequest::STATUS_PENDING => 'Menunggu',
                        DevelopmentRequest::STATUS_APPROVED => 'Disetujui',
                        DevelopmentRequest::STATUS_REJECTED => 'Ditolak',
                        DevelopmentRequest::STATUS_COMPLETED => 'Selesai',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        DevelopmentRequest::STATUS_PENDING => 'warning',
                        DevelopmentRequest::STATUS_APPROVED => 'info',
                        DevelopmentRequest::STATUS_REJECTED => 'danger',
                        DevelopmentRequest::STATUS_COMPLETED => 'success',
                    }),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    DevelopmentRequest::TYPE_TRAINING => 'Pelatihan',
                    DevelopmentRequest::TYPE_MENTORING => 'Mentoring',
                ]),
                SelectFilter::make('status')->options([
                    DevelopmentRequest::STATUS_PENDING => 'Menunggu',
                    DevelopmentRequest::STATUS_APPROVED => 'Disetujui',
                    DevelopmentRequest::STATUS_REJECTED => 'Ditolak',
                    DevelopmentRequest::STATUS_COMPLETED => 'Selesai',
                ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Setujui')
                    ->color('success')
                    ->schema([Textarea::make('notes')->label('Catatan')])
                    ->visible(fn (DevelopmentRequest $record): bool => auth()->user()?->role === UserRole::Manager
                        && $record->manager_id === auth()->id()
                        && $record->status === DevelopmentRequest::STATUS_PENDING)
                    ->action(fn (DevelopmentRequest $record, array $data) => $record->approve(auth()->user(), $data['notes'] ?? null)),
                Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->schema([Textarea::make('notes')->label('Alasan')->required()])
                    ->visible(fn (DevelopmentRequest $record): bool => auth()->user()?->role === UserRole::Manager
                        && $record->manager_id === auth()->id()
                        && $record->status === DevelopmentRequest::STATUS_PENDING)
                    ->action(fn (DevelopmentRequest $record, array $data) => $record->reject(auth()->user(), $data['notes'])),
                Action::make('complete')
                    ->label('Selesaikan')
                    ->color('success')
                    ->schema([Textarea::make('notes')->label('Hasil')])
                    ->visible(fn (DevelopmentRequest $record): bool => $record->status === DevelopmentRequest::STATUS_APPROVED
                        && (auth()->user()?->role === UserRole::Hr
                            || (auth()->user()?->role === UserRole::Manager && $record->manager_id === auth()->id())))
                    ->action(fn (DevelopmentRequest $record, array $data) => $record->complete(auth()->user(), $data['notes'] ?? null)),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListDevelopmentRequests::route('/')];
    }
}
