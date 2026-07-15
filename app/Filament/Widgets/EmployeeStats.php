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
    protected function getStats(): array
    {
        return [
            Stat::make('Dinas aktif', DutyTrip::where('employee_id', auth()->id())->where('status', DutyTripStatus::Approved)->count())
                ->description('Lihat tugas yang sedang berjalan')
                ->color('primary')
                ->icon(Heroicon::OutlinedBriefcase)
                ->url(DutyTripResource::getUrl()),
            Stat::make('Dinas selesai', DutyTrip::where('employee_id', auth()->id())->where('status', DutyTripStatus::Completed)->count())
                ->description('Buka riwayat dinas')
                ->color('success')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->url(DutyTripResource::getUrl()),
            Stat::make('Riwayat absensi', Attendance::where('employee_id', auth()->id())->count())
                ->description('Lihat bukti dan status absensi')
                ->color('info')
                ->icon(Heroicon::OutlinedMapPin)
                ->url(AttendanceResource::getUrl()),
            Stat::make('Hasil merit terbit', MeritResult::where('employee_id', auth()->id())->whereNotNull('published_at')->count())
                ->description('Lihat hasil penilaian merit')
                ->color('warning')
                ->icon(Heroicon::OutlinedTrophy)
                ->url(MeritResultResource::getUrl()),
            Stat::make('Pelatihan disetujui', TrainingRequest::where('user_id', auth()->id())->where('status', TrainingRequestStatus::Approved)->count())
                ->description('Lihat status pengajuan pelatihan')
                ->color('success')
                ->icon(Heroicon::OutlinedBookOpen)
                ->url(TrainingRequestResource::getUrl()),
            Stat::make('Mentoring terjadwal', Mentoring::where('employee_id', auth()->id())->where('status', MentoringStatus::Approved)->count())
                ->description('Lihat jadwal mentoring')
                ->color('primary')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->url(MentoringResource::getUrl()),
        ];
    }
}
