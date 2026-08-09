# Testing Absensi Dinas Multi-hari

Bekukan waktu Laravel, lalu gunakan waktu Laravel tersebut sebagai `captured_at` di halaman absensi. Jam Windows/WSL tidak perlu diubah.

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

3. Di `resources/views/attendance/capture.blade.php`, ubah hanya sumber waktu `captured_at` agar mengambil `now()` Laravel yang sudah dibekukan:

```js
captured_at: @json(now()->toIso8601String()),
```

Kamera, foto, GPS, dan tombol simpan tetap memakai alur halaman asli.

4. Pastikan waktu Laravel sudah sesuai:

```bash
php artisan tinker --execute="dump(now()->toDateTimeString());"
```

5. Jalankan server, buka ulang halaman dinas, lalu lakukan absensi hari pertama.

### Simulasikan hari berikutnya

Ubah tanggal `Carbon::setTestNow()` ke hari berikutnya, misalnya:

```php
Carbon::setTestNow('2026-07-30 08:00:00');
```

Baris JavaScript tidak perlu diubah karena nilainya selalu mengikuti `now()` Laravel. Refresh halaman dinas, ambil foto dan lokasi seperti biasa, lalu simpan absensi. Sistem harus membuat baris absensi baru untuk 30 Juli. Ulangi langkah yang sama untuk 31 Juli.

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
