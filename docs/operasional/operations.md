# Operasional dan Deployment

## Jadwal Otomatis

Cron server wajib menjalankan scheduler Laravel setiap menit:

```cron
* * * * * cd /path/aplikasi && php artisan schedule:run >> /dev/null 2>&1
```

Foto absensi melewati `PHOTO_RETENTION_DAYS` dihapus pukul 03:00. Jalankan manual bila diperlukan:

```bash
php artisan attendance:purge-photos --days=365
```

Backup MySQL tidak dijalankan oleh scheduler Laravel. Jadwalkan `mysqldump` pada host atau layanan backup infrastruktur.

## Backup MySQL

Simpan backup bernama timestamp di lokasi privat. File baru dipindahkan ke nama akhir setelah dump sukses:

```bash
backup_dir=storage/app/private/backups
backup_file="$backup_dir/database-$(date +%Y%m%d-%H%M%S).sql"
tmp_file="$backup_file.tmp"
mkdir -p "$backup_dir"
chmod 700 "$backup_dir"
umask 077
if docker compose exec -T mysql sh -c 'exec mysqldump --single-transaction --quick --no-tablespaces -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' > "$tmp_file"; then
    mv "$tmp_file" "$backup_file"
else
    rm -f "$tmp_file"
    exit 1
fi
find "$backup_dir" -type f -name 'database-*.sql' -mtime +14 -delete
```

Salin backup ke media terpisah. Volume Docker bukan pengganti backup.

## Simulasi Restore MySQL

Uji restore pada database staging, bukan database produksi:

1. Aktifkan maintenance: `php artisan down`.
2. Buat backup database aktif.
3. Pulihkan dump:

```bash
backup_file=storage/app/private/backups/database-YYYYMMDD-HHMMSS.sql
docker compose exec -T mysql sh -c 'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < "$backup_file"
```

4. Jalankan `php artisan migrate --force`.
5. Jalankan test dan pemeriksaan data pada staging.
6. Nonaktifkan maintenance: `php artisan up`.

Uji restore wajib dilakukan berkala. Backup belum terbukti sampai restore berhasil.

## Checklist Deployment

- `APP_ENV=production`, `APP_DEBUG=false`, dan HTTPS aktif.
- `APP_KEY`, kredensial MySQL, dan API key unik serta tidak masuk repository.
- Google Maps API key dibatasi domain, API, dan kuota.
- Direktori `storage` dan `bootstrap/cache` dapat ditulis proses aplikasi.
- Queue worker dan scheduler aktif serta dimonitor.
- File absensi dan backup tidak berada pada disk publik.
- `php artisan migrate --force`, `php artisan optimize`, formatter, build, dan test lulus.
- Login dan authorization tiga peran diuji.
- GPS, kamera depan, watermark, mode luring, dan sinkronisasi diuji pada smartphone nyata.
- Backup MySQL dibuat dan restore staging berhasil.
