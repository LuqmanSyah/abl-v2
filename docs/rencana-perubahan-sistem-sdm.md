# Rencana Perubahan Sistem SDM

## Tujuan

Menjadikan sistem benar sebagai aplikasi SDM internal untuk:

- absensi dinas lapangan;
- penilaian kinerja dan merit sederhana;
- pengembangan kompetensi melalui pelatihan dan mentoring.

Sistem tidak diposisikan sebagai HRIS lengkap atau mesin payroll.

## Prinsip

- Perbaiki makna dan validitas data sebelum menambah fitur.
- Pertahankan schema database bila perubahan label/UI cukup.
- Gunakan workflow, `ActivityLog`, transaksi, dan validasi yang sudah ada.
- Jangan tambah dependency atau tabel baru tanpa kebutuhan terukur.
- Nilai yang belum lengkap tidak boleh diam-diam menjadi nol.

## Fase 1 — Benahi Istilah dan Posisi Produk

### Perubahan

- Ubah label UI `Absensi` menjadi `Absensi Dinas` pada konteks perjalanan dinas.
- Ubah label `Disiplin` menjadi `Kepatuhan Dinas`.
- Ubah label `Review 360` menjadi `Umpan Balik Rekan`.
- Ubah label `Estimasi Bonus` menjadi `Simulasi Bonus` dan tampilkan keterangan bahwa hasil tidak terhubung ke payroll.
- Perjelas ruang lingkup produk di `README.md`.
- Pertahankan nama kolom database lama untuk menghindari migration dan perubahan massal yang tidak memberi nilai bisnis.

### Area terdampak

- `README.md`
- resource dan widget Attendance
- form Review Period
- table/infolist/widget Merit Result
- laporan HR

### Selesai jika

- Tidak ada UI yang menyebut absensi dinas sebagai attendance management umum.
- Tidak ada UI yang mengklaim peer feedback sebagai review 360 lengkap.
- Bonus selalu disebut simulasi, bukan nominal pembayaran final.

## Fase 2 — Amankan Perhitungan Merit

### Perubahan

Tambahkan pemeriksaan kelengkapan di `MeritCalculator::calculate()` sebelum hasil disimpan:

- Bobot KPI lebih dari nol: total bobot indikator periode wajib tepat 100% dan pegawai wajib memiliki KPI untuk setiap indikator.
- Bobot penilaian atasan lebih dari nol: satu `ManagerToEmployee` review wajib tersedia.
- Bobot umpan balik rekan lebih dari nol: minimal satu `Peer` review wajib tersedia.
- `EmployeeToManager` tidak dihitung sebagai umpan balik untuk merit pegawai.
- Data wajib yang belum lengkap menghasilkan `BusinessRuleException` berisi daftar komponen yang kurang.

Benahi kepatuhan dinas:

- Tidak memiliki tugas dinas selesai pada periode berarti skor kepatuhan 100, bukan 0.
- Memiliki tugas dinas tetapi tidak memiliki absensi valid tetap mengurangi skor.
- Perjalanan multi-hari tetap dihitung per tanggal unik seperti implementasi sekarang.

Benahi finalisasi:

- Perhitungan draft boleh dilakukan sebelum periode selesai.
- Publikasi HR hanya boleh dilakukan setelah `ends_at` periode terlewati.
- Rekomendasi pelatihan oleh atasan hanya boleh memakai Merit Result yang sudah dipublikasikan.
- Rumus simulasi bonus tetap sederhana: `base_bonus * total_score / 100`.

### File utama

- `app/Services/MeritCalculator.php`
- `app/Models/MeritResult.php`
- `app/Models/TrainingRequest.php`
- `tests/Feature/MeritSystemTest.php`
- `tests/Feature/CareerDevelopmentTest.php`
- `tests/Feature/FlowTest.php`

### Test wajib

- Merit ditolak saat KPI wajib belum lengkap.
- Merit ditolak saat review atasan atau peer wajib belum tersedia.
- `EmployeeToManager` tidak memenuhi kebutuhan peer feedback.
- Pegawai tanpa dinas mendapat skor kepatuhan 100.
- Dinas tanpa absensi valid tetap menghasilkan skor kepatuhan rendah.
- HR tidak dapat mempublikasikan hasil sebelum periode selesai.
- Merit belum terpublikasi tidak dapat menjadi dasar rekomendasi pelatihan.

### Selesai jika

- Tidak ada missing data yang berubah menjadi skor nol tanpa penjelasan.
- Total merit hanya berasal dari komponen lengkap.
- Simulasi bonus tidak dianggap hasil payroll.

## Fase 3 — Perketat Validasi Absensi Dinas

### Perubahan

- Tambahkan `attendance_max_accuracy_meters` ke `config/hr.php`.
- GPS `accuracy_meters` kosong atau melebihi batas menghasilkan `NeedsReview`.
- Tambahkan alasan review khusus untuk akurasi GPS.
- Pertahankan geofence, toleransi jam perangkat, foto, idempotency, notifikasi HR, dan row lock yang sudah ada.
- Jangan menambah face recognition atau liveness detection pada fase ini.

### File utama

- `config/hr.php`
- `app/Services/AttendanceRecorder.php`
- `tests/Feature/DutyAttendanceTest.php`

### Test wajib

- Akurasi GPS tepat pada batas tetap diterima.
- Akurasi melewati batas menghasilkan `NeedsReview`.
- Akurasi kosong menghasilkan `NeedsReview`.
- Validasi geofence dan clock mismatch lama tetap lulus.

### Selesai jika

- Koordinat dengan kualitas buruk tidak dapat menghasilkan status `Valid` otomatis.
- Batas akurasi dapat dikalibrasi lewat environment/config tanpa mengubah kode.

## Fase 4 — Audit Penilaian Kompetensi

### Perubahan

- Catat create, update, dan delete `EmployeeCompetency` melalui `ActivityLog` yang sudah ada.
- Simpan nilai lama/baru, tanggal penilaian, catatan, dan pengguna HR yang melakukan perubahan di log.
- Tolak `assessed_at` yang berada di masa depan.
- Pertahankan satu nilai kompetensi terkini per pegawai; riwayat diambil dari audit log.
- Jangan menaikkan level kompetensi otomatis setelah pelatihan selesai. HR tetap melakukan asesmen ulang.

### File utama

- `app/Models/EmployeeCompetency.php`
- `tests/Feature/CareerDevelopmentTest.php`

### Test wajib

- Perubahan level menghasilkan audit old/new yang benar.
- Aktor HR tercatat.
- Tanggal asesmen masa depan ditolak.
- `CareerGapService` memakai nilai terbaru setelah asesmen diperbarui.

### Selesai jika

- Setiap perubahan kompetensi dapat ditelusuri tanpa tabel history baru.
- Gap karier tidak berubah otomatis hanya karena pelatihan ditandai selesai.

## Urutan Pengerjaan

1. Fase 1: istilah dan positioning.
2. Fase 2: validitas merit.
3. Fase 3: kualitas GPS.
4. Fase 4: audit kompetensi.

Setiap fase harus menjadi perubahan kecil yang dapat diuji dan direview sendiri.

## Di Luar Scope

- payroll dan pajak;
- absensi reguler, shift, cuti, lembur, dan timesheet;
- review 360 lengkap, self-review, calibration, dan continuous feedback;
- face recognition, liveness detection, dan live tracking;
- LMS, sertifikasi, dan evaluasi efektivitas pelatihan;
- IDP baru, 9-box, succession planning, talent marketplace, dan AI.

Tambahkan fitur tersebut hanya setelah ada kebutuhan pengguna, kebijakan bisnis, dan data yang jelas.

## Definition of Done

Untuk setiap fase:

- test baru membuktikan aturan bisnis yang diubah;
- `vendor/bin/pint --dirty` lulus;
- `php artisan test` lulus;
- browser smoke test lulus untuk halaman yang label atau aksinya berubah;
- `git diff --check` bersih;
- tidak ada dependency, abstraction, atau tabel baru tanpa kebutuhan langsung.
