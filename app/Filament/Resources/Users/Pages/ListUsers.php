<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\MeritResult;
use App\Models\ReviewPeriod;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('export')
                ->label('Ekspor CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->schema([
                    Select::make('review_period_id')
                        ->label('Periode')
                        ->options(ReviewPeriod::query()->latest('starts_at')->pluck('name', 'id'))
                        ->required(),
                ])
                ->action(fn (array $data): StreamedResponse => $this->downloadCsv((int) $data['review_period_id'])),
        ];
    }

    private function downloadCsv(int $periodId): StreamedResponse
    {
        $period = ReviewPeriod::findOrFail($periodId);
        $range = [$period->starts_at->startOfDay(), $period->ends_at->endOfDay()];
        $users = $this->getFilteredTableQuery()
            ->where('role', UserRole::Employee)
            ->with(['unit', 'position'])
            ->withCount([
                'attendances as attendances_count' => fn (Builder $query) => $query
                    ->where('status', AttendanceStatus::Valid)
                    ->whereBetween('received_at', $range),
                'employeeKpis as employee_kpis_count' => fn (Builder $query) => $query
                    ->where('review_period_id', $period->id),
                'developmentRequests as development_requests_count' => fn (Builder $query) => $query
                    ->whereBetween('created_at', $range),
            ])
            ->addSelect([
                'merit_score' => MeritResult::select('total_score')
                    ->whereColumn('employee_id', 'users.id')
                    ->where('review_period_id', $period->id)
                    ->limit(1),
            ])
            ->orderBy('name');

        return response()->streamDownload(function () use ($users): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Nomor', 'Nama', 'Unit', 'Jabatan', 'Absensi valid', 'KPI', 'Merit', 'Pengembangan']);

            $users->each(fn (User $user) => fputcsv($output, [
                $user->employee_number,
                $user->name,
                $user->unit?->name,
                $user->position?->name,
                $user->attendances_count,
                $user->employee_kpis_count,
                $user->merit_score,
                $user->development_requests_count,
            ]));

            fclose($output);
        }, 'laporan-sdm-'.today()->toDateString().'.csv');
    }
}
