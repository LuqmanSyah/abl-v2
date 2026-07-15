<?php

namespace Tests\Unit;

use App\Services\SqliteBackup;
use SQLite3;
use Tests\TestCase;

class SqliteBackupTest extends TestCase
{
    public function test_backup_is_valid_and_restorable(): void
    {
        $source = tempnam(sys_get_temp_dir(), 'abl-source-');
        $target = tempnam(sys_get_temp_dir(), 'abl-backup-');
        unlink($target);

        try {
            $database = new SQLite3($source);
            $database->exec('CREATE TABLE checks (value TEXT)');
            $database->exec("INSERT INTO checks VALUES ('ok')");
            $database->close();

            app(SqliteBackup::class)->create($source, $target);

            $restored = new SQLite3($target, SQLITE3_OPEN_READONLY);
            $this->assertSame('ok', $restored->querySingle('PRAGMA integrity_check'));
            $this->assertSame('ok', $restored->querySingle('SELECT value FROM checks'));
            $restored->close();
        } finally {
            @unlink($source);
            @unlink($target);
        }
    }
}
