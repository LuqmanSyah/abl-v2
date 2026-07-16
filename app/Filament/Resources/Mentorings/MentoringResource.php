<?php

namespace App\Filament\Resources\Mentorings;

use App\Enums\MentoringStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Mentorings\Pages\ListMentorings;
use App\Models\Mentoring;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MentoringResource extends Resource
{
    protected static ?string $model = Mentoring::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Pengembangan';

    protected static ?int $navigationSort = 70;

    protected static ?string $modelLabel = 'mentoring';

    protected static ?string $pluralModelLabel = 'mentoring';

    public static function getNavigationLabel(): string
    {
        return match (auth()->user()?->role) {
            UserRole::Employee => 'Pengajuan Mentoring',
            UserRole::Manager => 'Pengelolaan Mentoring',
            UserRole::Hr => 'Monitoring Mentoring',
            default => 'Mentoring',
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
            Hidden::make('employee_id')->default(fn (): ?int => auth()->id()),
            Hidden::make('manager_id')->default(fn (): ?int => auth()->user()?->manager_id),
            Hidden::make('status')->default(MentoringStatus::Pending->value),
            TextInput::make('topic')->label('Topik')->required(),
            Textarea::make('target')->label('Target')->required()->columnSpanFull(),
            DateTimePicker::make('requested_at')->label('Jadwal yang diajukan')->native(false)->minDate(now())->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')->label('Pegawai')->searchable(),
                TextColumn::make('topic')->label('Topik')->searchable(),
                TextColumn::make('target')->label('Target')->limit(50),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof MentoringStatus ? $state->label() : (string) $state),
                TextColumn::make('requested_at')->label('Jadwal diajukan')->dateTime(),
                TextColumn::make('scheduled_at')->label('Jadwal disetujui')->dateTime()->placeholder('-'),
                TextColumn::make('result')->label('Hasil')->limit(50)->placeholder('-'),
                TextColumn::make('follow_up')->label('Tindak lanjut')->limit(50)->placeholder('-'),
            ])
            ->recordActions([
                Action::make('approve')->label('Jadwalkan')->color('success')
                    ->modalHeading('Jadwalkan Mentoring')
                    ->modalDescription('Tentukan jadwal mentoring dan tambahkan catatan bila diperlukan.')
                    ->modalSubmitActionLabel('Simpan Jadwal')
                    ->schema([
                        DateTimePicker::make('scheduled_at')->label('Jadwal')->native(false)->minDate(now())->required(),
                        Textarea::make('notes')->label('Catatan'),
                    ])
                    ->visible(fn ($record): bool => auth()->user()->role === UserRole::Manager
                        && $record->manager_id === auth()->id()
                        && $record->status === MentoringStatus::Pending)
                    ->action(fn ($record, array $data) => $record->approve(auth()->user(), $data['scheduled_at'], $data['notes'] ?? null)),
                Action::make('reject')->label('Tolak')->color('danger')
                    ->modalHeading('Tolak Pengajuan Mentoring')
                    ->modalDescription('Pengajuan akan ditolak. Alasan penolakan wajib diisi.')
                    ->modalSubmitActionLabel('Tolak Pengajuan')
                    ->schema([Textarea::make('notes')->label('Alasan')->required()])
                    ->visible(fn ($record): bool => auth()->user()->role === UserRole::Manager
                        && $record->manager_id === auth()->id()
                        && $record->status === MentoringStatus::Pending)
                    ->action(fn ($record, array $data) => $record->reject(auth()->user(), $data['notes'])),
                Action::make('complete')->label('Catat Hasil')->color('success')
                    ->modalHeading('Selesaikan Mentoring')
                    ->modalDescription('Catat hasil diskusi dan tindak lanjut sebelum menutup mentoring.')
                    ->modalSubmitActionLabel('Simpan Hasil')
                    ->schema([
                        Textarea::make('result')->label('Hasil diskusi')->required(),
                        Textarea::make('follow_up')->label('Tindak lanjut')->required(),
                    ])
                    ->visible(fn ($record): bool => auth()->user()->role === UserRole::Manager
                        && $record->manager_id === auth()->id()
                        && $record->status === MentoringStatus::Approved)
                    ->action(fn ($record, array $data) => $record->complete(auth()->user(), $data['result'], $data['follow_up'])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListMentorings::route('/')];
    }
}
