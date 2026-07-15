# Rencana Implementasi Sistem SDM

Sumber kebutuhan: `docs/brd.md` versi 1.2.

## Keputusan Teknis

- Laravel 12 dan Filament 5 berjalan lokal; MySQL 8.4 berjalan dalam Docker.
- Tiga panel: `/pegawai`, `/atasan`, dan `/hr`. Resource, query, serta aksi tetap dibatasi server-side per peran.
- Peran disimpan langsung pada `users.role`; belum perlu package permission.
- Workflow memakai enum status dan policy/action server-side. Tombol tersembunyi bukan pengamanan.
- Koordinat memakai `decimal(10, 7)`; jarak dihitung dengan rumus Haversine.
- Google Maps memakai API key dari environment. Key wajib dibatasi per domain dan API di Google Cloud.
- Foto kamera, GPS, watermark, penyimpanan luring, dan sinkronisasi memerlukan halaman mobile khusus/PWA. Filament menangani administrasi dan monitoring.
- Formula merit awal disimpan sebagai bobot konfigurasi. Pembayaran bonus tetap di luar sistem.

## Workflow Pengguna

1. **HR menyiapkan sistem:** data Pegawai, unit, jabatan, relasi Atasan, lokasi dinas, periode KPI, indikator, standar kompetensi, dan katalog pelatihan.
2. **Atasan memberi pekerjaan:** membuat perintah dinas bagi bawahan dan menetapkan target serta capaian KPI.
3. **Pegawai menjalankan pekerjaan:** melihat tugas, melakukan absensi, melihat KPI, dan mengisi penilaian 360 derajat.
4. **Sistem menghitung merit:** HR menjalankan perhitungan; Atasan memverifikasi; HR memublikasikan; Pegawai baru dapat melihat hasil.
5. **Pegawai merencanakan karier:** memilih jabatan tujuan; sistem menampilkan gap kompetensi serta rekomendasi pelatihan atau mentoring.
6. **Pengembangan diproses:** Pegawai mengajukan pelatihan/mentoring; Atasan memutuskan; HR memverifikasi dan mencatat hasil pelatihan; Atasan mencatat hasil mentoring.
7. **HR memantau:** dashboard, laporan terfilter, ekspor CSV, riwayat keputusan, retensi foto, dan backup database.

Menu utama per panel:

- `/pegawai`: Dinas Saya, Absensi, KPI, Penilaian 360, Merit, Kompetensi, Target Karier, Pelatihan, Pengajuan Pelatihan, Mentoring.
- `/atasan`: Perintah Dinas, Absensi Bawahan, KPI Bawahan, Penilaian 360, Verifikasi Merit, Kompetensi/Target Karier Bawahan, Persetujuan Pelatihan, Mentoring.
- `/hr`: Organisasi, Lokasi/Monitoring Dinas, Absensi, Periode/KPI/Merit, Kompetensi/Karier, Pelatihan/Mentoring, Activity Log, Laporan SDM.

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
- Perintah dinas oleh atasan untuk bawahan langsung, dokumen pendukung, map picker, dan status workflow.
- Pegawai melihat tugas tanpa mengubahnya; atasan dapat mengubah atau membatalkan sebelum waktu mulai.
- Halaman absensi mobile mengambil GPS dan foto kamera langsung.
- Validasi jadwal, Haversine/radius, watermark foto, dan status absensi.
- IndexedDB menyimpan antrean luring; service worker menyinkronkan ulang.
- Riwayat dan monitoring sesuai scope pengguna.

Verifikasi:

- Test perintah hanya dibuat atasan untuk bawahan langsung.
- Test lokasi tidak berubah setelah tugas selesai.
- Test jarak di dalam/luar radius dan batas waktu.
- Test endpoint sinkronisasi idempotent.
- Uji manual perangkat: izin GPS, `capture="user"`, mode luring, sinkronisasi.

BRD: FR-ABS-01 sampai FR-ABS-13, NFR-03, NFR-06 sampai NFR-08.

## Fase 2 — KPI dan Merit

Status: selesai.

Hasil:

- Periode penilaian, indikator KPI, bobot, target, dan capaian.
- Penilaian 360: atasan, bawahan, dan rekan kerja.
- Kalkulasi komponen KPI, kedisiplinan, penilaian atasan, dan 360.
- Formula per periode: bobot wajib berjumlah 100%; capaian KPI dibatasi 120%; estimasi bonus adalah dasar bonus dikali skor merit.
- Verifikasi berurutan oleh atasan dan HR.
- Skor, rincian komponen, serta estimasi bonus tampil setelah verifikasi.

Verifikasi:

- Total bobot KPI valid dan perhitungan punya test angka tetap.
- Pengguna tidak dapat menilai diri sendiri atau mengirim dua kali pada periode sama.
- Skor belum terlihat sebelum dua tahap verifikasi.

BRD: FR-MRT-01 sampai FR-MRT-08.

## Fase 3 — Pembinaan Karier

Status: selesai.

Hasil:

- Kompetensi, level, standar per jabatan, dan kompetensi pegawai.
- Jalur dari jabatan saat ini ke jabatan tujuan.
- Gap kompetensi dan rekomendasi pengembangan.
- Katalog serta pengajuan pelatihan, persetujuan atasan, verifikasi HR, hasil.
- Pengajuan, jadwal, status, dan catatan mentoring oleh atasan.
- Scope data: Pegawai hanya miliknya, Atasan hanya bawahan langsung, HR seluruh data.

Verifikasi:

- Test gap level kompetensi dan rekomendasi terkait.
- Test workflow pelatihan serta mentoring per peran.
- Pegawai hanya melihat data pengembangan sendiri.

BRD: FR-KAR-01 sampai FR-KAR-10.

## Fase 4 — Laporan dan Operasional

Status: selesai untuk implementasi. Uji staging dan perangkat nyata tetap wajib sebelum deployment.

Hasil:

- Dashboard HR untuk absensi, merit, pelatihan, dan mentoring.
- Laporan Pegawai dengan filter periode/unit/jabatan dan ekspor CSV aman dari formula injection.
- Riwayat aktivitas untuk perintah dinas, publikasi merit, keputusan pelatihan, dan mentoring.
- Retensi foto terjadwal melalui Laravel scheduler; backup MySQL memakai `mysqldump` dari host.
- Panduan restore, checklist deployment, responsive report, authorization, file privat, dan eager-loaded report query.

Verifikasi:

- Test scope laporan, filter, ekspor, dan formula injection.
- Test file privat tidak dapat diakses tanpa otorisasi dan foto kedaluwarsa dihapus.
- Backup MySQL diuji melalui restore staging; prosedur dan checklist deployment tersedia di `docs/operations.md`.

BRD: NFR-02, NFR-04, NFR-05, NFR-09, data utama, dan kriteria keberhasilan.

## Urutan Migrasi Data

1. `users`, `units`, `positions`.
2. `duty_locations`, `duty_trips`, `attendances`.
3. `review_periods`, `kpi_indicators`, `employee_kpis`, `reviews`, `merit_results`.
4. `competencies`, `position_competency`, `employee_competencies`, `career_goals`.
5. `trainings`, `training_requests`, `mentorings`, `activity_logs`.

## Definition of Done

- Semua aksi sensitif punya authorization server-side.
- Constraint database menjaga data unik dan relasi penting.
- Setiap rumus/workflow non-trivial punya minimal satu test gagal-lalu-lulus.
- `php artisan test` dan formatter lulus.
- `.env.example` mendokumentasikan konfigurasi eksternal tanpa menyimpan secret.
- Fitur perangkat wajib diuji lewat HTTPS pada smartphone nyata sebelum deployment; langkahnya tercantum di `docs/operations.md`.
