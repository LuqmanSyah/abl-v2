# Operasional dan Deployment

## Jadwal Otomatis

Cron server wajib menjalankan scheduler Laravel setiap menit:

```cron
* * * * * cd /path/aplikasi && php artisan schedule:run >> /dev/null 2>&1
```

| Waktu | Perintah |
| --- | --- |
| Setiap hari 02:00 | `backup:database` untuk SQLite atau `db:backup` untuk MySQL |
| Setiap hari 06:00 | `approval:escalate` |
| Setiap hari 09:00 | `merit:remind-kpi` |
| Tanggal 1, 00:05 | `merit:calculate` |
| Tanggal 1, 01:00 | `merit:send-report` |

## Backup Database

Backup disimpan pada disk lokal di folder `backups`. Jumlah file yang dipertahankan diatur lewat `BACKUP_KEEP`, default `14`.

Jalankan manual sesuai driver:

```bash
php artisan backup:database --keep=14
php artisan db:backup --keep=14
```

`backup:database` khusus SQLite dan dapat mengunggah hasil ke cloud disk yang dikonfigurasi. Gunakan `--no-cloud` untuk menonaktifkannya. `db:backup` khusus MySQL dan membutuhkan `mysqldump` pada host aplikasi.

Salin backup ke media terpisah dan uji restore berkala pada lingkungan non-production.

## Checklist Deployment

- `APP_ENV=production`, `APP_DEBUG=false`, dan HTTPS aktif.
- `APP_KEY` serta kredensial database unik dan tidak masuk repository.
- Direktori `storage` dan `bootstrap/cache` dapat ditulis proses aplikasi.
- Scheduler aktif dan dimonitor.
- File absensi dan backup tidak berada pada disk publik.
- `php artisan migrate --force`, `php artisan optimize`, formatter, build, dan test lulus.
- Login dan authorization tiga peran diuji.
- GPS dan kamera diuji pada perangkat nyata melalui HTTPS.
- Backup dibuat dan restore pada lingkungan non-production berhasil.
