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
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ManagerStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $managerId = auth()->id();

        return [
            Stat::make('Dinas aktif', DutyTrip::where('manager_id', $managerId)->where('status', DutyTripStatus::Approved)->count())
                ->description('Pantau tugas yang sedang berjalan')
                ->color('primary')
                ->icon(Heroicon::OutlinedBriefcase)
                ->url(DutyTripResource::getUrl()),
            Stat::make('Total tugas bawahan', DutyTrip::where('manager_id', $managerId)->count())
                ->description('Buka seluruh perintah dinas')
                ->color('gray')
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->url(DutyTripResource::getUrl()),
            Stat::make('Absensi bawahan', Attendance::whereHas('dutyTrip', fn ($query) => $query->where('manager_id', $managerId))->count())
                ->description('Periksa riwayat absensi')
                ->color('info')
                ->icon(Heroicon::OutlinedMapPin)
                ->url(AttendanceResource::getUrl()),
            Stat::make('Merit perlu verifikasi', MeritResult::whereHas('employee', fn ($query) => $query->where('manager_id', $managerId))->whereNull('manager_verified_at')->count())
                ->description('Perlu tindakan Anda')
                ->color('warning')
                ->icon(Heroicon::OutlinedTrophy)
                ->url(MeritResultResource::getUrl()),
            Stat::make('Pelatihan perlu persetujuan', TrainingRequest::where('manager_id', $managerId)->where('status', TrainingRequestStatus::PendingManager)->count())
                ->description('Perlu keputusan Anda')
                ->color('warning')
                ->icon(Heroicon::OutlinedBookOpen)
                ->url(TrainingRequestResource::getUrl()),
            Stat::make('Mentoring perlu jadwal', Mentoring::where('manager_id', $managerId)->where('status', MentoringStatus::Pending)->count())
                ->description('Perlu dijadwalkan')
                ->color('warning')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->url(MentoringResource::getUrl()),
            Stat::make('Anggota tim', User::where('manager_id', $managerId)->where('is_active', true)->count())
                ->description('Total pegawai aktif')
                ->color('primary')
                ->icon(Heroicon::OutlinedUsers),
        ];
    }
}
