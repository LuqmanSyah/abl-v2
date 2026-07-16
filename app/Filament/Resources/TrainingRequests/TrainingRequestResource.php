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
use UnitEnum;

class TrainingRequestResource extends Resource
{
    protected static ?string $model = TrainingRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Pengembangan';

    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'pengajuan pelatihan';

    protected static ?string $pluralModelLabel = 'pengajuan pelatihan';

    public static function getNavigationLabel(): string
    {
        return match (auth()->user()?->role) {
            UserRole::Employee => 'Pengajuan Pelatihan',
            UserRole::Manager => 'Persetujuan Pelatihan',
            UserRole::Hr => 'Verifikasi Pelatihan',
            default => 'Pengajuan Pelatihan',
        };
    }

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
                ->relationship('training', 'name', fn (Builder $query) => $query
                    ->where('is_active', true)
                    ->whereDoesntHave('requests', fn (Builder $query) => $query->where('user_id', auth()->id())))
                ->searchable()
                ->preload()
                ->required()
                ->helperText('Pelatihan yang pernah diajukan dikelola dari baris pengajuan sebelumnya.'),
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
                Action::make('resubmit')->label('Ajukan Ulang')->color('primary')
                    ->icon('heroicon-o-arrow-path')
                    ->modalHeading('Ajukan Ulang Pelatihan')
                    ->modalDescription('Perbarui alasan pengajuan sebelum dikirim kembali kepada Atasan.')
                    ->modalSubmitActionLabel('Kirim Ulang')
                    ->schema([Textarea::make('reason')->label('Alasan pengajuan terbaru')->required()])
                    ->visible(fn ($record): bool => auth()->user()->role === UserRole::Employee
                        && $record->user_id === auth()->id()
                        && $record->status === TrainingRequestStatus::Rejected)
                    ->action(fn ($record, array $data) => $record->resubmit(auth()->user(), $data['reason']))
                    ->successNotificationTitle('Pengajuan dikirim ulang kepada Atasan'),
                Action::make('approve_manager')->label('Setujui')->color('success')
                    ->modalHeading('Setujui Pengajuan Pelatihan')
                    ->modalDescription('Pengajuan akan diteruskan kepada HR untuk verifikasi.')
                    ->modalSubmitActionLabel('Setujui Pengajuan')
                    ->schema([Textarea::make('notes')->label('Catatan')])
                    ->visible(fn ($record): bool => auth()->user()->role === UserRole::Manager
                        && $record->manager_id === auth()->id()
                        && $record->status === TrainingRequestStatus::PendingManager)
                    ->action(fn ($record, array $data) => $record->approveByManager(auth()->user(), $data['notes'] ?? null)),
                Action::make('reject_manager')->label('Tolak')->color('danger')
                    ->modalHeading('Tolak Pengajuan Pelatihan')
                    ->modalDescription('Pengajuan akan dikembalikan kepada Pegawai dengan alasan penolakan.')
                    ->modalSubmitActionLabel('Tolak Pengajuan')
                    ->schema([Textarea::make('notes')->label('Alasan')->required()])
                    ->visible(fn ($record): bool => auth()->user()->role === UserRole::Manager
                        && $record->manager_id === auth()->id()
                        && $record->status === TrainingRequestStatus::PendingManager)
                    ->action(fn ($record, array $data) => $record->rejectByManager(auth()->user(), $data['notes'])),
                Action::make('verify_hr')->label('Verifikasi HR')->color('success')->requiresConfirmation()
                    ->modalHeading('Verifikasi Pengajuan Pelatihan')
                    ->modalDescription('Pengajuan akan disetujui dan tersedia untuk ditindaklanjuti oleh HR.')
                    ->modalSubmitActionLabel('Verifikasi Pengajuan')
                    ->modalWidth('md')
                    ->visible(fn ($record): bool => auth()->user()->role === UserRole::Hr && $record->status === TrainingRequestStatus::PendingHr)
                    ->action(fn ($record) => $record->verifyByHr(auth()->user())),
                Action::make('complete')->label('Catat Hasil')->color('success')
                    ->modalHeading('Selesaikan Pelatihan')
                    ->modalDescription('Catat hasil pelatihan sebelum menandai pengajuan sebagai selesai.')
                    ->modalSubmitActionLabel('Simpan Hasil')
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
