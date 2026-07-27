<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDeploymentTimezone extends Command
{
    protected $signature = 'deployment:check-timezone';

    protected $description = 'Check application, PHP, and database clocks';

    public function handle(): int
    {
        $expected = 'Asia/Jakarta';
        $driver = DB::connection()->getDriverName();
        $database = $driver === 'mysql'
            ? DB::selectOne(
                'SELECT NOW() AS current_time, @@session.time_zone AS db_timezone, '
                .'TIMEDIFF(NOW(), UTC_TIMESTAMP()) AS utc_offset',
            )
            : DB::selectOne('SELECT CURRENT_TIMESTAMP AS current_time');
        $databaseTime = CarbonImmutable::parse(
            $database->current_time,
            $driver === 'mysql' ? $expected : 'UTC',
        );
        $clockDifference = abs(CarbonImmutable::now('UTC')->diffInSeconds($databaseTime->utc()));
        $databaseTimezone = $driver === 'mysql' ? $database->db_timezone : 'UTC (SQLite)';
        $databaseOffsetValid = $driver !== 'mysql' || $database->utc_offset === '07:00:00';

        $this->table(['Layer', 'Timezone'], [
            ['Laravel', config('app.timezone')],
            ['PHP', date_default_timezone_get()],
            ['Database', $databaseTimezone],
        ]);

        if (config('app.timezone') !== $expected
            || date_default_timezone_get() !== $expected
            || ! $databaseOffsetValid
            || $clockDifference > 5) {
            $this->error("Timezone mismatch or clock drift ({$clockDifference}s).");

            return self::FAILURE;
        }

        $this->info("Timezone and clocks aligned ({$clockDifference}s drift).");

        return self::SUCCESS;
    }
}
