# Sprint — Merit Bulanan dan Status Update

## Tujuan

Merit dihitung per bulan berdasarkan `ReviewPeriod`. HR dapat menghitung ulang draft selama hasil belum diverifikasi. Setelah Atasan verifikasi atau HR publish, hasil terkunci untuk audit.

## Scope

- Tambah timestamp `merit_results.calculated_at` sebagai waktu hitung/update merit terakhir.
- `MeritCalculator::calculate()` mengisi `calculated_at` setiap hitung atau hitung ulang.
- Hitung ulang ditolak jika `manager_verified_at`, `hr_verified_at`, atau `published_at` sudah terisi.
- Halaman hasil merit menampilkan:
  - `Sudah di-update pada {tanggal}`;
  - `Sudah diverifikasi Atasan pada {tanggal}`;
  - `Sudah diverifikasi HR pada {tanggal}`;
  - `Sudah dipublikasikan pada {tanggal}`.

## Alur

1. HR membuat `ReviewPeriod` bulanan, misalnya Juli 2026.
2. Atasan mengisi KPI target dan capaian.
3. Sistem memakai data absensi, KPI, review atasan, dan review 360 dalam periode tersebut.
4. HR menjalankan hitung merit.
5. Jika data berubah sebelum verifikasi, HR boleh hitung ulang.
6. Atasan verifikasi hasil.
7. HR verifikasi dan publish.
8. Pegawai melihat hasil setelah publish.

## Acceptance Criteria

- Hitung pertama membuat `MeritResult` dan mengisi `calculated_at`.
- Hitung ulang sebelum verifikasi memperbarui score dan `calculated_at`.
- Hitung ulang setelah verifikasi Atasan ditolak dengan pesan jelas.
- Hitung ulang setelah publish ditolak.
- Tanggal update dan verifikasi tampil di table/detail hasil merit.
- Published merit tetap locked.

## Test

- `test_monthly_merit_can_be_recalculated_before_verification_with_update_timestamp`
- `test_verified_merit_cannot_be_recalculated`
- Regression existing: formula merit, publish lock, visibility pegawai, edit lock KPI/periode.

## Catatan

- Scheduler bulanan belum dibuat. HR masih trigger hitung merit manual dari panel.
- `updated_at` tidak dipakai sebagai waktu hitung merit karena berubah saat verifikasi.
