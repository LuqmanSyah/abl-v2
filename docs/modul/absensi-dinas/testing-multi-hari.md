# Testing Absensi Dinas Multi-hari

Gunakan waktu palsu yang sama di Laravel dan payload JavaScript halaman absensi. Jam Windows/WSL tidak perlu diubah.

### Persiapan

1. Gunakan database lokal, bukan produksi.
2. Buat dan setujui dinas yang mencakup seluruh tanggal simulasi, misalnya 29–31 Juli 2026.
3. Login sebagai pegawai yang ditugaskan.

### Atur tanggal simulasi

1. Di `app/Providers/AppServiceProvider.php`, tambahkan import berikut:

```php
use Illuminate\Support\Carbon;
```

2. Tambahkan `Carbon::setTestNow()` di awal method `boot()`. Jangan hapus isi `boot()` yang sudah ada.

```php
public function boot(): void
{
    Carbon::setTestNow('2026-07-29 08:00:00');

    // Isi boot() yang sudah ada tetap di bawah sini.
}
```

3. Di `resources/views/attendance/capture.blade.php`, ubah nilai `captured_at` agar tanggal dan jamnya sama. Offset `+07:00` mengikuti zona waktu aplikasi `Asia/Jakarta`.

```js
const data = {
    captured_at: new Date('2026-07-29T08:00:00+07:00').toISOString(),
    latitude: position.coords.latitude,
    longitude: position.coords.longitude,
    accuracy_meters: Math.round(position.coords.accuracy),
};
```

4. Pastikan waktu Laravel sudah sesuai:

```bash
php artisan tinker --execute="dump(now()->toDateTimeString());"
```

5. Jalankan server, buka ulang halaman dinas, lalu lakukan absensi hari pertama.

### Simulasikan hari berikutnya

Ubah kedua nilai waktu ke tanggal berikutnya, misalnya:

```php
Carbon::setTestNow('2026-07-30 08:00:00');
```

```js
captured_at: new Date('2026-07-30T08:00:00+07:00').toISOString(),
```

Refresh halaman dinas, lalu lakukan absensi lagi. Sistem harus membuat baris absensi baru untuk 30 Juli. Ulangi langkah yang sama untuk 31 Juli.

### Setelah selesai

1. Hapus import `Illuminate\Support\Carbon` dan pemanggilan `Carbon::setTestNow()` dari `AppServiceProvider`.
2. Kembalikan JavaScript ke waktu perangkat:

```js
captured_at: new Date().toISOString(),
```

3. Jalankan ulang server dan pastikan waktu Laravel kembali normal:

```bash
php artisan tinker --execute="dump(now()->toDateTimeString());"
```

Jangan commit atau deploy `Carbon::setTestNow()`. Selama baris tersebut aktif, seluruh waktu aplikasi membeku pada waktu simulasi.
