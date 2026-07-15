# Operasional dan Deployment

## Jadwal Otomatis

Cron server wajib menjalankan scheduler Laravel setiap menit:

```cron
* * * * * cd /path/aplikasi && php artisan schedule:run >> /dev/null 2>&1
```

- Backup SQLite berjalan pukul 02:00. Jumlah backup mengikuti `BACKUP_KEEP`.
- Foto absensi melewati `PHOTO_RETENTION_DAYS` dihapus pukul 03:00.
- Backup tersimpan privat pada `storage/app/private/backups`.

Jalankan manual:

```bash
php artisan backup:database --keep=14
php artisan attendance:purge-photos --days=365
```

Database selain SQLite wajib memakai alat native database, misalnya `pg_dump` atau `mysqldump`.

## Simulasi Restore SQLite

1. Aktifkan maintenance: `php artisan down`.
2. Buat salinan database aktif.
3. Pilih backup terbaru dari `storage/app/private/backups`.
4. Validasi backup: `sqlite3 BACKUP_FILE "PRAGMA integrity_check;"` harus menghasilkan `ok`.
5. Salin backup ke path `DB_DATABASE`.
6. Jalankan `php artisan migrate --force`.
7. Jalankan `php artisan test` pada lingkungan staging.
8. Nonaktifkan maintenance: `php artisan up`.

Uji restore wajib dilakukan di staging sebelum prosedur dipakai pada produksi.

## Checklist Deployment

- `APP_ENV=production`, `APP_DEBUG=false`, dan HTTPS aktif.
- `APP_KEY` unik dan secret tidak masuk repository.
- Google Maps API key dibatasi domain, API, dan kuota.
- Direktori `storage` dan `bootstrap/cache` dapat ditulis proses aplikasi.
- Queue worker dan scheduler aktif serta dimonitor.
- File absensi dan backup tidak berada pada disk publik.
- `php artisan migrate --force`, `php artisan optimize`, formatter, dan test lulus.
- Login dan authorization tiga role diuji.
- GPS, kamera depan, watermark, mode luring, dan sinkronisasi diuji pada smartphone nyata.
- Backup dibuat, integrity check lulus, dan restore staging berhasil.
