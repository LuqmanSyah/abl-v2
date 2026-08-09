<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Mail\ReportMail;
use App\Models\ReviewPeriod;
use App\Models\User;
use App\Services\HrReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendReport extends Command
{
    protected $signature = 'merit:send-report {--review_period_id=} {--unit_id=} {--position_id=}';

    protected $description = 'Kirim laporan SDM periodik ke HR via email';

    public function handle(HrReportService $reports): int
    {
        $periodId = $this->option('review_period_id');
        $unitId = $this->option('unit_id');
        $positionId = $this->option('position_id');

        $period = $periodId ? ReviewPeriod::find($periodId) : ReviewPeriod::where('is_active', true)->latest('ends_at')->first();
        $filters = [
            'review_period_id' => $period?->id,
            'unit_id' => $unitId ? (int) $unitId : null,
            'position_id' => $positionId ? (int) $positionId : null,
        ];

        $rows = $reports->rows($filters);

        if ($rows->isEmpty()) {
            $this->warn('Tidak ada data laporan.');

            return 0;
        }

        $periods = ReviewPeriod::orderByDesc('starts_at')->get();
        $dateLabel = now()->format('Y-m-d');

        $hrUsers = User::where('role', UserRole::Hr)->where('is_active', true)->get();

        if ($hrUsers->isEmpty()) {
            $this->warn('Tidak ada pengguna HR aktif.');

            return 0;
        }

        foreach ($hrUsers as $hr) {
            Mail::to($hr->email)->send(new ReportMail($rows, $filters, $periods, $dateLabel));
        }

        $this->info("Laporan dikirim ke {$hrUsers->count()} HR.");

        return 0;
    }
}
