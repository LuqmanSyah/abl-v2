<?php

namespace App\Filament\Widgets;

use App\Enums\DutyTripStatus;
use App\Enums\MentoringStatus;
use App\Enums\TrainingRequestStatus;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Filament\Resources\Mentorings\MentoringResource;
use App\Filament\Resources\MeritResults\MeritResultResource;
use App\Filament\Resources\TrainingRequests\TrainingRequestResource;
use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Models\Mentoring;
use App\Models\MeritResult;
use App\Models\TrainingRequest;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmployeeStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $userId = auth()->id();
        $today = today();

        return [
            Stat::make('Dinas aktif', DutyTrip::where('employee_id', $userId)->where('status', DutyTripStatus::Approved)->where('ends_at', '>=', now())->count())
                ->description('Lihat tugas yang sedang berjalan')
                ->color('primary')
                ->icon(Heroicon::OutlinedBriefcase)
                ->url(DutyTripResource::getUrl()),
            Stat::make('Dinas selesai', DutyTrip::where('employee_id', $userId)->where('status', DutyTripStatus::Approved)->where('ends_at', '<', now())->count())
                ->description('Buka riwayat dinas')
                ->color('success')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->url(DutyTripResource::getUrl()),
            Stat::make('Riwayat absensi dinas', Attendance::where('employee_id', $userId)->count())
                ->description('Lihat bukti dan status absensi dinas')
                ->color('info')
                ->icon(Heroicon::OutlinedMapPin)
                ->url(AttendanceResource::getUrl()),
            Stat::make('Hasil merit terbit', MeritResult::where('employee_id', $userId)->whereNotNull('published_at')->count())
                ->description('Lihat hasil penilaian merit')
                ->color('warning')
                ->icon(Heroicon::OutlinedTrophy)
                ->url(MeritResultResource::getUrl()),
            Stat::make('Pelatihan disetujui', TrainingRequest::where('user_id', $userId)->where('status', TrainingRequestStatus::Approved)->count())
                ->description('Lihat status pengajuan pelatihan')
                ->color('success')
                ->icon(Heroicon::OutlinedBookOpen)
                ->url(TrainingRequestResource::getUrl()),
            Stat::make('Mentoring terjadwal', Mentoring::where('employee_id', $userId)->where('status', MentoringStatus::Approved)->count())
                ->description('Lihat jadwal mentoring')
                ->color('primary')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->url(MentoringResource::getUrl()),
            Stat::make('Absensi dinas hari ini', Attendance::where('employee_id', $userId)->whereDate('captured_at', $today)->count())
                ->description('Status absensi dinas hari ini')
                ->color(Attendance::where('employee_id', $userId)->whereDate('captured_at', $today)->exists() ? 'success' : 'warning')
                ->icon(Heroicon::OutlinedClock)
                ->url(AttendanceResource::getUrl()),
        ];
    }
}
