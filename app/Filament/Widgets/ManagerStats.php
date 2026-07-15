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

class ManagerStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Dinas aktif', DutyTrip::where('manager_id', auth()->id())->where('status', DutyTripStatus::Approved)->count())
                ->description('Pantau tugas yang sedang berjalan')
                ->color('primary')
                ->icon(Heroicon::OutlinedBriefcase)
                ->url(DutyTripResource::getUrl()),
            Stat::make('Total tugas bawahan', DutyTrip::where('manager_id', auth()->id())->count())
                ->description('Buka seluruh perintah dinas')
                ->color('gray')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->url(DutyTripResource::getUrl()),
            Stat::make('Absensi bawahan', Attendance::whereHas('dutyTrip', fn ($query) => $query->where('manager_id', auth()->id()))->count())
                ->description('Periksa riwayat absensi')
                ->color('info')
                ->icon(Heroicon::OutlinedMapPin)
                ->url(AttendanceResource::getUrl()),
            Stat::make('Merit perlu verifikasi', MeritResult::whereHas('employee', fn ($query) => $query->where('manager_id', auth()->id()))->whereNull('manager_verified_at')->count())
                ->description('Perlu tindakan Anda')
                ->color('warning')
                ->icon(Heroicon::OutlinedTrophy)
                ->url(MeritResultResource::getUrl()),
            Stat::make('Pelatihan perlu persetujuan', TrainingRequest::where('manager_id', auth()->id())->where('status', TrainingRequestStatus::PendingManager)->count())
                ->description('Perlu keputusan Anda')
                ->color('warning')
                ->icon(Heroicon::OutlinedBookOpen)
                ->url(TrainingRequestResource::getUrl()),
            Stat::make('Mentoring perlu jadwal', Mentoring::where('manager_id', auth()->id())->where('status', MentoringStatus::Pending)->count())
                ->description('Perlu dijadwalkan')
                ->color('warning')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->url(MentoringResource::getUrl()),
        ];
    }
}
