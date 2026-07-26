<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceRequestStatus;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use BackedEnum;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
                    ->where('user_id', auth()->id())
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
                ->required()
                ->disk('local')
                ->directory('attendance')
                ->visibility('private')
                ->label('Foto'),

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
                IconColumn::make('is_fallback')
                    ->boolean()
                    ->label('Fallback'),
                TextColumn::make('recorded_at')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->label('Waktu'),
            ])
            ->defaultSort('recorded_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return Filament::getCurrentPanel()?->getId() === 'employee'
            ? $query->whereBelongsTo(auth()->user())
            : $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
        ];
    }
}
