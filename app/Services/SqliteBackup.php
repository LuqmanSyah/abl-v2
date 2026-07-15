<?php

namespace App\Services;

use RuntimeException;
use SQLite3;

class SqliteBackup
{
    public function create(string $sourcePath, string $targetPath): void
    {
        if (! is_file($sourcePath)) {
            throw new RuntimeException('File database SQLite tidak ditemukan.');
        }

        $source = new SQLite3($sourcePath, SQLITE3_OPEN_READONLY);
        $target = new SQLite3($targetPath);

        try {
            if (! $source->backup($target) || $target->querySingle('PRAGMA integrity_check') !== 'ok') {
                throw new RuntimeException('Backup SQLite gagal atau tidak valid.');
            }
        } finally {
            $source->close();
            $target->close();
        }
    }
}
