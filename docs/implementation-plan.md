# Rencana Implementasi Sistem SDM

Sumber kebutuhan: `docs/brd.md` versi 1.2.

## Keputusan Teknis

- Laravel 12, Filament 5, database bawaan proyek.
- Tiga panel: `/pegawai`, `/atasan`, dan `/hr`. Resource, query, serta aksi tetap dibatasi server-side per peran.
- Peran disimpan langsung pada `users.role`; belum perlu package permission.
- Workflow memakai enum status dan policy/action server-side. Tombol tersembunyi bukan pengamanan.
- Koordinat memakai `decimal(10, 7)`; jarak dihitung dengan rumus Haversine.
- Google Maps memakai API key dari environment. Key wajib dibatasi per domain dan API di Google Cloud.
- Foto kamera, GPS, watermark, penyimpanan luring, dan sinkronisasi memerlukan halaman mobile khusus/PWA. Filament menangani administrasi dan monitoring.
- Formula merit awal disimpan sebagai bobot konfigurasi. Pembayaran bonus tetap di luar sistem.

## Fase 0 — Fondasi

Status: selesai.

Hasil:

- Filament 5 terpasang dan panel dapat dibuka.
- Login/logout aktif.
- Role `pegawai`, `atasan`, `hr` membatasi akses panel dan data.
- Data organisasi: pengguna, unit kerja, jabatan, relasi atasan.
- Seeder akun demo untuk tiga peran.

Verifikasi:

- Test akses panel tiap peran.
- HR dapat CRUD pegawai, unit, dan jabatan.
- Pegawai/Atasan tidak dapat membuka resource HR melalui URL langsung.

BRD: FR-USR-01 sampai FR-USR-03, NFR-01.

## Fase 1 — Absensi Dinas

Status: selesai. Uji perangkat nyata tetap wajib sebelum deployment.

Hasil:

- Master lokasi dinas dan radius geofence.
- Pengajuan dinas, dokumen pendukung, map picker, dan status workflow.
- Aksi setujui/tolak oleh atasan; lokasi terkunci setelah disetujui.
- Halaman absensi mobile mengambil GPS dan foto kamera langsung.
- Validasi jadwal, Haversine/radius, watermark foto, dan status absensi.
- IndexedDB menyimpan antrean luring; service worker menyinkronkan ulang.
- Riwayat dan monitoring sesuai scope pengguna.

Verifikasi:

- Test pengajuan hanya milik pegawai dan persetujuan hanya oleh atasannya.
- Test lokasi tidak berubah setelah persetujuan.
- Test jarak di dalam/luar radius dan batas waktu.
- Test endpoint sinkronisasi idempotent.
- Uji manual perangkat: izin GPS, `capture="user"`, mode luring, sinkronisasi.

BRD: FR-ABS-01 sampai FR-ABS-13, NFR-03, NFR-06 sampai NFR-08.

## Fase 2 — KPI dan Merit

Hasil:

- Periode penilaian, indikator KPI, bobot, target, dan capaian.
- Penilaian 360: atasan, bawahan, dan rekan kerja.
- Kalkulasi komponen KPI, kedisiplinan, penilaian atasan, dan 360.
- Verifikasi berurutan oleh atasan dan HR.
- Skor, rincian komponen, serta estimasi bonus tampil setelah verifikasi.

Verifikasi:

- Total bobot KPI valid dan perhitungan punya test angka tetap.
- Pengguna tidak dapat menilai diri sendiri atau mengirim dua kali pada periode sama.
- Skor belum terlihat sebelum dua tahap verifikasi.

BRD: FR-MRT-01 sampai FR-MRT-08.

## Fase 3 — Pembinaan Karier

Hasil:

- Kompetensi, level, standar per jabatan, dan kompetensi pegawai.
- Jalur dari jabatan saat ini ke jabatan tujuan.
- Gap kompetensi dan rekomendasi pengembangan.
- Katalog serta pengajuan pelatihan, persetujuan atasan, verifikasi HR, hasil.
- Pengajuan, jadwal, status, dan catatan mentoring oleh atasan.

Verifikasi:

- Test gap level kompetensi dan rekomendasi terkait.
- Test workflow pelatihan serta mentoring per peran.
- Pegawai hanya melihat data pengembangan sendiri.

BRD: FR-KAR-01 sampai FR-KAR-10.

## Fase 4 — Laporan dan Operasional

Hasil:

- Dashboard HR untuk absensi, merit, pelatihan, dan mentoring.
- Filter periode/unit/jabatan dan ekspor CSV.
- Riwayat aktivitas untuk perubahan penting dan semua keputusan workflow.
- Aturan retensi foto, backup terjadwal, serta panduan restore.
- Pemeriksaan responsive, authorization, perlindungan file, dan query besar.

Verifikasi:

- Test scope laporan dan ekspor.
- Test file privat tidak dapat diakses tanpa otorisasi.
- Simulasi backup/restore dan checklist deployment.

BRD: NFR-02, NFR-04, NFR-05, NFR-09, data utama, dan kriteria keberhasilan.

## Urutan Migrasi Data

1. `users`, `units`, `positions`.
2. `duty_locations`, `duty_trips`, `attendances`.
3. `review_periods`, `kpi_indicators`, `employee_kpis`, `reviews`, `merit_results`.
4. `competencies`, standar/kompetensi pegawai, jalur karier.
5. `trainings`, `training_requests`, `mentorings`.
6. `activity_logs`.

## Definition of Done

- Semua aksi sensitif punya authorization server-side.
- Constraint database menjaga data unik dan relasi penting.
- Setiap rumus/workflow non-trivial punya minimal satu test gagal-lalu-lulus.
- `php artisan test` dan formatter lulus.
- `.env.example` mendokumentasikan konfigurasi eksternal tanpa menyimpan secret.
- Fitur perangkat diuji lewat HTTPS pada smartphone nyata.
