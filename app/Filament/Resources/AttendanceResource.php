<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceRequestStatus;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\UserRole;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AttendanceResource extends RoleAwareResource
{
    protected static ?string $model = Attendance::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|UnitEnum|null $navigationGroup = 'Kehadiran';

    protected static ?string $modelLabel = 'Presensi GPS';

    protected static ?string $pluralModelLabel = 'Presensi GPS';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->options(AttendanceType::class)
                ->default(fn (): ?string => request()->query('type'))
                ->required()
                ->live()
                ->label('Jenis Presensi'),

            Select::make('attendance_request_id')
                ->options(fn () => AttendanceRequest::query()
                    ->where('user_id', Auth::id())
                    ->where('status', AttendanceRequestStatus::Approved)
                    ->whereDate('duty_start_datetime', '<=', today())
                    ->whereDate('duty_end_datetime', '>=', today())
                    ->pluck('destination_name', 'id'))
                ->placeholder('Kantor biasa')
                ->label('Tugas Luar'),

            View::make('filament.forms.components.gps-capture'),
            Hidden::make('latitude')->required(),
            Hidden::make('longitude')->required(),

            FileUpload::make('photo_path')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(5120)
                ->extraInputAttributes([
                    'accept' => 'image/*',
                    'capture' => 'user',
                ])
                ->required()
                ->disk('local')
                ->directory('attendance')
                ->visibility('private')
                ->label('Live Selfie'),

            Textarea::make('exception_reason')
                ->visible(fn (Get $get): bool => $get('type') === AttendanceType::CheckOut->value)
                ->maxLength(500)
                ->helperText('Wajib jika lokasi check-out berada di luar radius.')
                ->label('Alasan Check-Out Luar Radius'),
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
                TextColumn::make('type')
                    ->badge()
                    ->label('Jenis'),
                TextColumn::make('attendanceRequest.destination_name')
                    ->placeholder('Kantor biasa')
                    ->label('Lokasi'),
                TextColumn::make('distance_to_target_meters')
                    ->suffix(' m')
                    ->numeric()
                    ->label('Jarak'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (AttendanceStatus $state): string => match ($state) {
                        AttendanceStatus::Normal => 'success',
                        AttendanceStatus::Late, AttendanceStatus::Alfa => 'danger',
                        AttendanceStatus::PendingVerification => 'warning',
                        AttendanceStatus::Rejected => 'gray',
                    })
                    ->label('Status'),
                TextColumn::make('evidence')
                    ->state(fn (Attendance $record): string => $record->canViewEvidence(Auth::user())
                        ? 'Lihat'
                        : '-')
                    ->url(fn (Attendance $record): ?string => $record->canViewEvidence(Auth::user())
                        ? route('attendance.evidence', $record)
                        : null)
                    ->openUrlInNewTab()
                    ->label('Selfie'),
                TextColumn::make('exception_reason')
                    ->wrap()
                    ->placeholder('-')
                    ->label('Alasan Exception'),
                IconColumn::make('is_fallback')
                    ->boolean()
                    ->label('Fallback'),
                TextColumn::make('recorded_at')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->label('Waktu'),
            ])
            ->defaultSort('recorded_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(AttendanceStatus::class)
                    ->label('Status'),
            ])
            ->actions([
                Action::make('approve_exception')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Attendance $record): bool => $record->canBeVerifiedBy(Auth::user()))
                    ->action(fn (Attendance $record) => $record->approveException(Auth::user())),
                Action::make('reject_exception')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Attendance $record): bool => $record->canBeVerifiedBy(Auth::user()))
                    ->action(fn (Attendance $record) => $record->rejectException(Auth::user())),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Filament::getCurrentPanel()?->getId() === 'employee') {
            return $query->whereBelongsTo(Auth::user());
        }

        $user = Auth::user();

        return $user instanceof User && $user->role === UserRole::Manager
            ? $query->whereHas('user', fn (Builder $query) => $query->where('manager_id', $user->id))
            : $query;
    }

    public static function canCreate(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'employee' && parent::canCreate();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
        ];
    }
}
