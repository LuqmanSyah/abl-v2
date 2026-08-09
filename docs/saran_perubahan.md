# Saran Perubahan Sistem Merit

- Sumber: audit kode — `app/Services/MeritCalculator.php`, `app/Services/AttendanceRecorder.php`, `app/Console/Commands/CalculateMerit.php`, `app/Console/Commands/SendReport.php`, `app/Http/Controllers/HrReportController.php`, dan model terkait.
- Tanggal: 9 Agustus 2026
- Cakupan: konsistensi dan kebenaran perhitungan KPI & merit, serta layanan yang saling terhubung (pencatatan absensi, kalkulasi merit, laporan).

## Kesimpulan

Formula perhitungan merit **sudah benar** dan seluruh kasus inti sudah dicover automated test (`MeritSystemTest`, 14 test). Alur siklus kalkulasi—fungsi, verifikasi berjenjang, publikasi dua tahap, dan larangan hitung ulang setelah verifikasi—sudah aman. Namun hasil audit menemukan beberapa risiko yang perlu diperbaiki:

- duplikasi query laporan yang rentan drift antarlayanan;
- bias skala disiplin saat periode review dan dinas tidak tepat satu baris;
- magic number kebijakan keras-di-kode;
- ketidakkonsistenan batas validasi bobot indikator.

## Temuan dan Saran

### P1. Duplikasi query laporan — `SendReport.php` dan `HrReportController.php`

**Prioritas:** tinggi

**Lokasi kode:**
- `app/Console/Commands/SendReport.php:59-108` (`reportRows`); dan
- `app/Http/Controllers/HrReportController.php:124-183` (`rows`).

**Masalah:** kedua sumber membangun query yang identik (filter, `with(['meritResults' …])`, `withCount`). Jika kebijakan laporan diubah di satu sumber, sumber lain bisa tertinggal — hasil akhirnya berisiko drift.

**Saran:**
- Ekstrak logika ke satu application service, misalnya `App\Services\HrReportService`, yang menyediakan metode `rows(array $filters)` dan dipakai oleh perintah `SendReport` dan controller `HrReportController`.
- Beri test untuk service tersebut sehingga kedua jalur (email dan ekspor) selalu menghasilkan format yang sama.

### P2. Pengaruh batas periode pada skor disiplin — `MeritCalculator`

**Prioritas:** tinggi

**Lokasi kode:** `app/Services/MeritCalculator.php:71-92`.

**Masalah:**
- Denominator `$allDates` mengambil rentang dinas penuh (`trip.starts_at … trip.ends_at`).
- Numerator `$validDates` hanya berisi kehadiran yang tercatat dalam rentang periode (`whereBetween('captured_at', [$periodStart, $periodEnd])`, baris 76-78).
- Jika dinas mulai sebelum tanggal mulai atau berakhir setelah tanggal selesai, maka beberapa hari di luar periode masuk ke denominator, sedangkan absensi valid hanya dihitung di dalam periode → skor disiplin terpuruk secara tidak wajar.

**Saran:**
- Klip rentang dinas ke rentang periode saat menyusun `$allDates` sehingga hari di luar periode tidak membebani denominator.
- Tambahkan test boundary: dinas dimulai sebelum `starts_at` dan dinas berakhir setelah `ends_at` periode.

## P3. Magic number kebijakan tertanam di kode

**Prioritas:** sedang

**Lokasi kode:**
- `MeritCalculator.php:64-67` — nilai *cap* rasio KPI `1.2`;
- `MeritCalculator.php:127-134` — normalisasi skala review `/5`;
- `MeritCalculator.php:92` — batas skor disiplin `100`.

**Masalahnya:** kebijakan tiap perusahaan berbeda-beda; kini tiap perubahan kebijakan wajib ubah kode.

**Saran:**
- Pindahkan ke `config/hr.php`, contoh: `merit.kpi_cap` (default 1.2), `merit.review_max_score` (default 5), `merit.discipline_max_score` (default 100).
- Bacalah nilai tersebut melalui `config('hr.merit.*')` di dalam `MeritCalculator`.

## P4. Guard bobot indikator tidak harmonis — `MeritCalculator` vs `KpiIndicator`

**Prioritas:** sedang

**Lokasi kode:**
- `app/Models/KpiIndicator.php:29-34` — validasi simpan indikator: total bobot ≤ 100;
- `app/Services/MeritCalculator.php:37-44` — syarat hitung: total bobot harus **sama dengan** 100 saat `kpi_weight > 0`.

**Masalah:** HR dapat menyimpan set indikator yang total bobotnya di bawah 100 (misalnya 90). Semua perhitungan merit pada periode tersebut kemudian diblokir dengan pesan generic `Data merit belum lengkap: KPI Pegawai`, tanpa menjelaskan bahwa bobot indikator kurang dari 100.

**Saran:**
- Samakan aturan: validasi `KpiIndicator` untuk mensyaratkan total bobot `=== 100` (pastikan skala 0-100), atau
- Ubah `MeritCalculator` agar menerima total bobot `< 100` (normalisasi bobot individu), sesuai kebijakan.
- Saat total tidak sama dengan 100, gunakan pesan error yang menyebutkan bahwa bobot indikator KPI belum mencapai 100%.

## Minor

- Absensi dengan `Late` tetap ikut tercatat (`AttendanceRecorder.php:62`); kebijakan menilai bahwa keterlambatan sebagai "hari tidak valid" untuk skor disiplin. Bukan bug — pastikan kebijakan resmi mengonfirmasi hal ini.
- Aturan `min(achievement/target, 1.2)` berarti skor KPI maksimal 120 (`MeritCalculator.php:66`). Pastikan kebijakan perusahaan memang memungkinkan capaian di atas target dibayar lebih dari 100.
- Pastikan seluruh nilai `target`/`achievement` memakai `decimal:2` secara konsisten (telah dikencangkan di `EmployeeKpi`).

## Audit Tambahan: Logic & Persiapan Kalkulasi Merit

Audit ulang difokuskan pada logika sebenarnya dari layanan inti merit: `MeritCalculator`, `AttendanceRecorder` (absensi sebagai input skor disiplin), dan pipeline kalkulasi `CalculateMerit` + laporan. **Logic inti sudah benar**: formula, guard, urutan status, dan concurrency cocok dengan seluruh assert test. Tidak ada error perhitungan (skor/bobot/bonus) yang keliru. Berikut temuan pendukung yang belum tercatat di atas.

### L1. Kelayakan karyawan di command `CalculateMerit` terlalu sempit

**Prioritas:** tinggi

**Lokasi kode:** `app/Console/Commands/CalculateMerit.php:37-44`.

**Masalah:**
- Filter karyawan: hanya yang memiliki `dutyTrips` tumpang tindih periode **atau** `employeeKpis` pada periode.
- Karyawan yang KPI-nya kosong dan tanpa dinas tak pernah lolos loop, sehingga tidak pernah punya hasil merit sama sekali — walaupun ia memiliki penilaian Atasan/Rekan yang lengkap (misal skenario `kpi_weight = 0`, `discipline_weight = 0`, `review_360_weight > 0`). Akibatnya skor `/` muncul di laporan dan karyawan tersebut lewat dari perhitungan.
- Komponen tak akan pernah menghasilkan error yang menjelaskan mengapa.

**Saran**:
- Tarik semua `User` dengan role `Employee` (aktif) pada periode; biarkan cek kelengkapan di `MeritCalculator` yang menolak.
- Tambahkan test: karyawan tanpa KPI dan tanpa dinas tapi dengan review lengkap tetap diperhitungkan (skor sesuai bobot).

### L2. Jendela periode default pada `CalculateMerit` menolak periode lama

**Prioritas:** sedang

**Lokasi kode:** `app/Console/Commands/CalculateMerit.php:23`.

**Masalah**:
- Query default: `{ scope() } where('is_active', true)->whereDate('ends_at', '>=', now()->subMonth())`.
- Periode yang sudah selesai lebih dari satu bulan yang lalu, atau dinonaktifkan (`is_active = false`), tidak ditangkap oleh default → tidak pernah dihitung diam-diam. Hanya bisa lewat `--period=` eksplisit.

**Saran**:
- Di jadwal bulanan, pastikan jendela `ends_at` menjangkau periode yang selesai bulan perhitungan (mis. awal bulan, `whereMonth('ends_at', ...)` atau `end of last month`).
- Tambahkan test yang menjalankan command dengan periode yang sudah selesai lebih dari 1 bulan lalu.

### L3. Catatan opsional — status review tidak difilter `submitted_at` dalam periode

**Prioritas:** ringan

**Lokasi kode:** `app/Services/MeritCalculator.php:46-58`.

**Masalah:** kalkulasi hanya memeriksa keberadaan review pada periode, tidak memeriksa bahwa `submitted_at` masih dalam rentang periode. Model `PerformanceReview` immutabilitas, tetapi laporan yang dikirim di luar periode ikut menjadi pertimbangan skor.

**Saran:** sepakati kebijakan: apakah review yang dikirim setelah periode selesai tetap masuk hitungan skor; jika ya, filter `submitted_at` antara `starts_at` dan `ends_at`.