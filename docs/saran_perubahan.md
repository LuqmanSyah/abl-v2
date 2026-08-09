# Saran Perubahan Sistem Merit

- Sumber audit: layanan kalkulasi merit, absensi, laporan, command terjadwal, dan model terkait.
- Tanggal audit: 9 Agustus 2026.
- Pembaruan implementasi: 9 Agustus 2026.
- Verifikasi terakhir: 95 test lulus dengan 574 assertion.

## Ringkasan Keputusan

| ID | Status | Keputusan |
| --- | --- | --- |
| P1 | Selesai | Query laporan dipusatkan di `HrReportService`. |
| P2 | Selesai | Rentang dinas diklip ke periode review. |
| P3 | Tidak diambil | Konfigurasi magic number ditunda sampai ada kebutuhan kebijakan nyata. |
| P4 | Selesai sebagian | Draft bobot tetap boleh di bawah 100; kalkulasi memberi error khusus jika total bukan 100. |
| L1 | Selesai | Kalkulasi batch memproses seluruh Pegawai aktif, termasuk periode review-only. |
| L2 | Dipertahankan | Jendela satu bulan sesuai scheduler; backfill memakai `--period`. |
| L3 | Ditunda | Perlu keputusan kebijakan tentang review yang dikirim setelah periode selesai. |

## Perubahan yang Selesai

### P1. Satu sumber data laporan HR

**Masalah:** `SendReport` dan `HrReportController` sebelumnya menduplikasi filter, eager load, hitungan absensi, pelatihan, mentoring, dan pemetaan baris laporan.

**Implementasi:**

- `app/Services/HrReportService.php:17` menyediakan `rows(array $filters)` sebagai sumber bersama.
- `app/Console/Commands/SendReport.php:32` memakai hasil service untuk email.
- `app/Http/Controllers/HrReportController.php:124` memakai hasil yang sama, lalu hanya menangani grouping untuk tampilan/ekspor.
- `tests/Feature/OperationsReportTest.php:120` memastikan baris web dan email identik.

### P2. Batas periode skor disiplin

**Masalah:** denominator memakai seluruh tanggal dinas, sedangkan absensi valid dibatasi periode. Dinas yang melintasi batas periode dapat menurunkan skor secara tidak wajar.

**Implementasi:**

- `app/Services/MeritCalculator.php:87-90` mengklip awal dan akhir dinas ke batas periode sebelum menyusun tanggal unik.
- `tests/Feature/MeritSystemTest.php:153` mencakup dinas yang dimulai sebelum dan berakhir setelah periode.

### P4. Pesan bobot indikator KPI

**Keputusan:** validasi `KpiIndicator` tetap membolehkan total sementara di bawah 100 agar HR dapat menyusun indikator satu per satu. Normalisasi otomatis tidak dilakukan karena dapat menyembunyikan konfigurasi yang belum lengkap.

**Implementasi:**

- `app/Services/MeritCalculator.php:39-42` menolak kalkulasi bila total bobot indikator bukan 100%.
- Pesan mencantumkan total aktual, contoh: `Total bobot indikator KPI wajib 100% (saat ini 90%).`
- `tests/Feature/MeritSystemTest.php:214` menjaga perilaku tersebut.

### L1. Kelayakan Pegawai dalam kalkulasi batch

**Masalah:** command hanya memilih Pegawai yang memiliki KPI atau dinas. Tombol Filament lebih sempit lagi karena hanya memilih Pegawai yang memiliki KPI. Periode berbobot review saja tidak menghasilkan merit.

**Implementasi:**

- `app/Services/MeritBatchCalculator.php:15` menjadi jalur batch bersama untuk command dan Filament.
- Seluruh pengguna aktif dengan role `Employee` dicoba; data tidak lengkap dilewati per Pegawai tanpa menghentikan Pegawai lain.
- Command mencatat setiap kegagalan ke log. Filament menampilkan jumlah berhasil dan dilewati.
- `tests/Feature/MeritSystemTest.php:327` mencakup review-only serta hasil batch parsial.

## Keputusan yang Tidak Diimplementasikan

### P3. Magic number kebijakan

Nilai cap KPI `1.2`, skala review `5`, dan batas disiplin `100` tetap di kode. Memindahkannya ke config hanya memindahkan literal dan belum membuat kebijakan dapat diedit HR.

Tinjau ulang jika nilai memang berbeda antar-deployment atau sering berubah tanpa rilis aplikasi.

### L2. Jendela periode default `CalculateMerit`

Filter periode aktif dengan `ends_at >= now()->subMonth()` dipertahankan. Command dijadwalkan setiap tanggal 1 sehingga jendela tersebut mencakup siklus sebelumnya. Perhitungan ulang periode lama tetap tersedia melalui `merit:calculate --period=N`.

Perluas jendela hanya jika sistem harus mengejar kegagalan scheduler lebih dari satu bulan secara otomatis.

### L3. Batas `submitted_at` review

Belum ada filter berdasarkan `submitted_at`. Kebijakan harus diputuskan lebih dahulu:

- jika review terlambat tetap boleh dihitung, perilaku sekarang dipertahankan;
- jika review terlambat dilarang, validasi dilakukan saat `PerformanceReview` dibuat, bukan dengan mengabaikannya diam-diam saat kalkulasi.

## Catatan Kebijakan yang Masih Terbuka

- `AttendanceStatus::Late` tersimpan tetapi tidak dihitung sebagai hari valid. Konfirmasi kebijakan resmi.
- Cap KPI 120% memungkinkan capaian di atas target menaikkan bonus. Konfirmasi kebijakan resmi.
- `EmployeeKpi.target` dan `achievement` sudah memakai cast `decimal:2`.

## Hasil Verifikasi

- `vendor/bin/pint --dirty`: lulus.
- `php artisan test --compact`: 95 test, 574 assertion, seluruhnya lulus.
- `git diff --check`: bersih.
- Alur tombol kalkulasi Filament diverifikasi melalui Livewire integration test; browser runner tidak tersedia di environment lokal.
