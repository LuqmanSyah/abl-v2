<?php

use App\Models\Attendance;
use App\Services\SqliteBackup;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:purge-photos {--days=365}', function () {
    $cutoff = now()->subDays(max(1, (int) $this->option('days')));
    $deleted = 0;

    Attendance::where('captured_at', '<', $cutoff)->eachById(function (Attendance $attendance) use (&$deleted): void {
        if ($attendance->photo_path && Storage::disk('local')->delete($attendance->photo_path)) {
            $deleted++;
        }
    });

    $this->info("{$deleted} foto kedaluwarsa dihapus.");
})->purpose('Hapus foto absensi melewati masa retensi');

Artisan::command('backup:database {--keep=14}', function () {
    if (DB::getDriverName() !== 'sqlite') {
        $this->error('Backup bawaan hanya mendukung SQLite. Gunakan alat native database untuk driver ini.');

        return 1;
    }

    $database = config('database.connections.sqlite.database');
    if ($database === ':memory:') {
        $this->error('Database in-memory tidak dapat dicadangkan.');

        return 1;
    }

    $directory = Storage::disk('local')->path('backups');
    File::ensureDirectoryExists($directory);
    $path = $directory.'/database-'.now()->format('Ymd-His-u').'.sqlite';
    app(SqliteBackup::class)->create($database, $path);

    collect(File::files($directory))
        ->filter(fn ($file) => $file->getExtension() === 'sqlite')
        ->sortByDesc(fn ($file) => $file->getMTime())
        ->skip(max(1, (int) $this->option('keep')))
        ->each(fn ($file) => File::delete($file->getPathname()));

    $this->info("Backup dibuat: {$path}");
})->purpose('Buat backup konsisten database SQLite');

if (config('database.default') === 'sqlite') {
    Schedule::command('backup:database --keep='.config('hr.backup_keep'))->dailyAt('02:00')->withoutOverlapping();
} else {
    Schedule::command('db:backup --keep='.config('hr.backup_keep', 14))->dailyAt('02:00')->withoutOverlapping();
}

Schedule::command('attendance:purge-photos --days='.config('hr.photo_retention_days'))->dailyAt('03:00')->withoutOverlapping();

Schedule::command('merit:calculate')->monthlyOn(1, '00:05')->withoutOverlapping();
Schedule::command('merit:remind-kpi')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('attendance:remind')->twiceDaily(8, 12)->withoutOverlapping();
Schedule::command('merit:send-report')->monthlyOn(1, '01:00')->withoutOverlapping();
Schedule::command('approval:escalate')->dailyAt('06:00')->withoutOverlapping();
