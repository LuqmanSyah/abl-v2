<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--keep=14}';

    protected $description = 'Buat backup MySQL via mysqldump';

    public function handle(): int
    {
        $driver = config('database.default');
        $keep = max(1, (int) $this->option('keep'));
        $dir = Storage::disk('local')->path('backups');
        File::ensureDirectoryExists($dir, 0700);

        if ($driver === 'mysql') {
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port');
            $db = config('database.connections.mysql.database');
            $user = config('database.connections.mysql.username');
            $pass = config('database.connections.mysql.password');

            $path = "{$dir}/database-".now()->format('Ymd-His').'.sql';
            $tmp = "{$path}.tmp";

            $cmd = sprintf(
                'MYSQL_PWD=%s mysqldump --host=%s --port=%s --user=%s --single-transaction --quick --no-tablespaces %s > %s',
                escapeshellarg($pass),
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($user),
                escapeshellarg($db),
                escapeshellarg($tmp),
            );

            $output = null;
            $rc = 0;
            exec($cmd, $output, $rc);

            if ($rc !== 0) {
                File::delete($tmp);
                $this->error('Backup MySQL gagal.');

                return 1;
            }

            rename($tmp, $path);
        } else {
            $this->warn("Driver {$driver} tidak didukung backup otomatis. Backup manual via mysqldump.");

            return 1;
        }

        collect(File::files($dir))
            ->filter(fn ($f) => in_array($f->getExtension(), ['sql', 'sqlite']))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->skip($keep)
            ->each(fn ($f) => File::delete($f->getPathname()));

        $this->info("Backup created: {$path}");

        return 0;
    }
}
