<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceStatus;
use App\Enums\DutyTripStatus;
use App\Enums\MentoringStatus;
use App\Enums\TrainingRequestStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Filament\Resources\Mentorings\MentoringResource;
use App\Filament\Resources\MeritResults\MeritResultResource;
use App\Filament\Resources\TrainingRequests\TrainingRequestResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Attendance;
use App\Models\DutyTrip;
use App\Models\Mentoring;
use App\Models\MeritResult;
use App\Models\TrainingRequest;
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HrStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Pegawai aktif', User::where('role', UserRole::Employee)->where('is_active', true)->count())
                ->description('Kelola data pegawai')
                ->color('primary')
                ->icon(Heroicon::OutlinedUsers)
                ->url(UserResource::getUrl()),
            Stat::make('Dinas aktif', DutyTrip::where('status', DutyTripStatus::Approved)->count())
                ->description('Pantau dinas yang berjalan')
                ->color('info')
                ->icon(Heroicon::OutlinedBriefcase)
                ->url(DutyTripResource::getUrl()),
            Stat::make('Absensi dinas hari ini', Attendance::whereDate('captured_at', today())->count())
                ->description('Lihat absensi dinas terbaru')
                ->color('success')
                ->icon(Heroicon::OutlinedMapPin)
                ->url(AttendanceResource::getUrl()),
            Stat::make('Merit perlu verifikasi HR', MeritResult::whereNotNull('manager_verified_at')->whereNull('hr_verified_at')->count())
                ->description('Perlu tindakan HR')
                ->color('warning')
                ->icon(Heroicon::OutlinedTrophy)
                ->url(MeritResultResource::getUrl()),
            Stat::make('Pelatihan perlu verifikasi', TrainingRequest::where('status', TrainingRequestStatus::PendingHr)->count())
                ->description('Perlu keputusan HR')
                ->color('warning')
                ->icon(Heroicon::OutlinedBookOpen)
                ->url(TrainingRequestResource::getUrl()),
            Stat::make('Mentoring aktif', Mentoring::where('status', MentoringStatus::Approved)->count())
                ->description('Pantau mentoring berjalan')
                ->color('primary')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->url(MentoringResource::getUrl()),
            Stat::make('Absensi dinas perlu review', Attendance::where('status', AttendanceStatus::NeedsReview)->count())
                ->description('Perlu dicek HR')
                ->color('danger')
                ->icon(Heroicon::OutlinedShieldExclamation)
                ->url(AttendanceResource::getUrl()),
        ];
    }
}
