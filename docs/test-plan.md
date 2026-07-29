# Test Plan — ABL Sistem SDM

> - Status: Draft
> - Versi: 1.0
> - Tanggal: 29 Juli 2026
> - Basis: implementasi aplikasi saat ini
> - Dokumen terkait: [Product Spec](product-spec.md) dan [Technical Design](technical-design.md)

## 1. Tujuan

Dokumen ini menentukan cara memastikan ABL Sistem SDM bekerja sesuai kebutuhan produk dan desain teknis.

Fokus test:

- hak akses HR, Atasan, dan Pegawai;
- perjalanan dinas dan absensi berbasis lokasi;
- KPI, penilaian, merit, dan publikasi hasil;
- kompetensi, pelatihan, mentoring, dan pengembangan karier;
- laporan, ekspor, notifikasi, scheduler, serta backup;
- keamanan data sensitif, khususnya foto dan data pegawai;
- regresi sebelum demo, penilaian mata kuliah, atau rilis.

Dokumen ini bukan bukti bahwa seluruh skenario sudah lulus. Status cakupan membedakan test yang sudah otomatis, masih manual, belum dibuat, atau menunggu keputusan produk.

## 2. Prinsip

1. Test mengikuti perilaku aktual dan acceptance baseline pada `product-spec.md`.
2. Business rule penting diuji pada lapisan model/service, bukan hanya tampilan.
3. Hak akses diuji pada panel, route, query data, dan aksi.
4. Alur utama diuji bersama alur gagal dan percobaan akses ilegal.
5. Test otomatis menjadi regresi utama.
6. Browser manual dipakai untuk kemampuan yang bergantung pada kamera, GPS, peta, layout, dan perangkat.
7. Keputusan produk yang masih TBD tidak dianggap defect sampai perilaku target diputuskan.

## 3. Istilah Status

| Status | Arti |
|---|---|
| `AUTO` | Sudah memiliki test otomatis |
| `PARTIAL` | Sebagian perilaku sudah otomatis; sisanya perlu test |
| `MANUAL` | Diverifikasi secara manual |
| `GAP` | Test belum tersedia |
| `TBD` | Expected result menunggu keputusan produk |
| `BLOCKED` | Tidak dapat diuji karena defect atau dependency |

## 4. Scope

### 4.1 In scope

- login, logout, akun aktif, dan redirect berbasis peran;
- isolasi panel dan data antarperan;
- struktur organisasi, unit, jabatan, dan hubungan Atasan–Pegawai;
- pembuatan, perubahan, pembatalan, dan visibilitas perjalanan dinas;
- pengambilan foto, koordinat, akurasi GPS, radius, waktu, serta idempotensi absensi;
- verifikasi absensi oleh HR;
- periode penilaian, indikator KPI, KPI Pegawai, dan penilaian kinerja;
- perhitungan, verifikasi, penguncian, dan publikasi merit;
- kompetensi, gap karier, pelatihan, mentoring, dan rekomendasi;
- dashboard, laporan operasional, filter, CSV/XLSX/PDF, dan foto privat;
- notification, queue, mail, scheduler command, seeder, dan backup;
- validasi, transaction, unique constraint, audit trail, dan failure handling;
- usability dasar, responsive layout, accessibility dasar, dan browser compatibility.

### 4.2 Out of scope saat ini

- load test skala produksi sebelum target volume ditentukan;
- penetration test formal oleh pihak eksternal;
- face recognition, liveness detection, dan mock-location detection;
- offline attendance;
- object storage;
- workflow engine approval dinamis;
- production deployment dan disaster recovery production;
- fitur yang belum disetujui dalam product spec.

Item out of scope masuk kembali setelah keputusan produk atau implementasi tersedia.

## 5. Acceptance Baseline

Rilis dianggap memenuhi baseline bila:

- setiap peran hanya membuka panel, data, dan aksi yang diizinkan;
- pengguna nonaktif tidak dapat login atau mempertahankan akses;
- Atasan hanya memberi perjalanan dinas dan KPI kepada bawahan langsung;
- Pegawai hanya mengirim absensi untuk tugasnya sendiri;
- absensi menyimpan foto privat, lokasi, jarak, waktu, dan status;
- pengiriman ulang tugas dan tanggal yang sama tidak membuat duplikasi;
- HR hanya memverifikasi absensi berstatus `NeedsReview`;
- total bobot merit selalu 100%;
- hasil merit baru terlihat oleh Pegawai setelah publikasi HR;
- training dan mentoring hanya mengikuti transition yang sah;
- proses penting menghasilkan riwayat aktivitas;
- laporan HR tidak dapat diakses peran lain.

## 6. Pendekatan Test

### 6.1 Unit test

Target:

- formula atau algoritma murni;
- konversi dan validasi nilai;
- perhitungan jarak;
- backup SQLite;
- helper yang tidak membutuhkan alur HTTP.

Unit test harus cepat dan tidak bergantung pada service eksternal.

### 6.2 Feature dan integration test

Target:

- route dan middleware;
- model, service, database, transaction, dan constraint;
- panel dan resource Filament;
- komponen Livewire;
- authorization dan query scoping;
- notification, export, command, serta workflow.

Test memakai database terisolasi dan fixture minimal.

### 6.3 End-to-end otomatis

Saat ini end-to-end diuji pada level HTTP, model, dan Livewire. Browser automation penuh belum tersedia.

Browser automation baru layak ditambah bila:

- alur UI sering regresi;
- proyek berlanjut setelah mata kuliah;
- kamera/GPS dapat dimock secara stabil;
- biaya pemeliharaan test sebanding dengan manfaat.

### 6.4 Manual exploratory test

Wajib untuk:

- kamera dan izin perangkat;
- geolocation dan akurasi GPS;
- Google Maps dan pemilihan lokasi;
- tampilan mobile;
- download file;
- interaksi multi-tab;
- kondisi jaringan lambat atau terputus;
- kejelasan pesan error dan notifikasi.

### 6.5 Non-functional test

Meliputi:

- security;
- accessibility dasar;
- compatibility;
- performance smoke;
- backup dan restore;
- operasional queue dan scheduler.

## 7. Lingkungan Test

### 7.1 Otomatis

| Komponen | Konfigurasi |
|---|---|
| PHP | Versi project yang didukung, minimal 8.2 |
| Framework | Laravel 12 |
| Database | SQLite in-memory |
| Queue | `sync` |
| Mail | `array` |
| Cache | `array` |
| Session | `array` |
| Runner | PHPUnit melalui `php artisan test` |

SQLite memberi test cepat, tetapi tidak membuktikan kompatibilitas penuh dengan MySQL.

### 7.2 Integrasi lokal

| Komponen | Konfigurasi |
|---|---|
| Database | MySQL 8.4 |
| Queue | Database queue |
| Session | Database |
| Cache | Database |
| Storage | Local private disk |
| Mail | Log |
| Frontend | Vite |
| Maps | Google Maps JavaScript API |

### 7.3 Browser dan perangkat minimum

Sebelum demo:

- Chrome desktop versi terbaru;
- Chrome Android pada satu perangkat fisik;
- viewport mobile sekitar 360 × 800;
- viewport desktop sekitar 1366 × 768;
- koneksi normal dan throttling lambat;
- izin kamera/lokasi diterima dan ditolak.

Safari/iOS diuji bila perangkat tersedia. Ketidaktersediaan dicatat pada test report.

## 8. Test Data

### 8.1 Data minimum

- satu akun HR aktif;
- satu Atasan aktif dengan minimal dua bawahan;
- satu Atasan lain;
- tiga Pegawai aktif dari minimal dua unit;
- satu pengguna nonaktif;
- unit, jabatan, competency, dan career path;
- perjalanan dinas hari ini, masa depan, multi-hari, dibatalkan, dan selesai;
- lokasi di dalam dan di luar radius;
- periode merit aktif dan terpublikasi;
- KPI lengkap dan tidak lengkap;
- training dan mentoring pada setiap status.

### 8.2 Aturan data

- test otomatis membuat datanya sendiri;
- urutan test tidak boleh saling bergantung;
- database testing dapat dibuat ulang;
- foto dummy tidak boleh tersisa setelah test gagal;
- data lokal disposable, kecuali dinyatakan penting;
- kredensial, API key, dan data personal nyata tidak dimasukkan ke fixture atau repository.

## 9. Entry Criteria

Eksekusi test dimulai bila:

- dependency PHP dan frontend terpasang;
- `.env.testing` atau konfigurasi PHPUnit valid;
- migration dapat dijalankan;
- application key tersedia pada lingkungan test;
- perubahan yang diuji sudah dapat dibangun;
- expected result untuk skenario non-TBD jelas.

Manual attendance test memerlukan HTTPS atau origin lokal yang mengizinkan kamera dan geolocation.

## 10. Exit Criteria

Perubahan siap demo atau digabung bila:

- `vendor/bin/pint --dirty` lulus;
- `php artisan test` lulus;
- `git diff --check` bersih;
- build frontend lulus bila asset frontend berubah;
- browser smoke lulus bila UI atau alur pengguna berubah;
- tidak ada defect Severity 1 atau Severity 2 terbuka;
- acceptance baseline terkait perubahan memiliki bukti test;
- GAP baru dicatat dan diterima, bukan disembunyikan.

Untuk rilis lebih serius:

- test MySQL lulus;
- queue worker dan scheduler smoke lulus;
- backup dapat direstore;
- checklist security dan accessibility selesai.

## 11. Inventaris Test Otomatis Saat Ini

Baseline saat dokumen dibuat: 86 test, 522 assertions.

| File | Jumlah | Cakupan utama |
|---|---:|---|
| `tests/Feature/CareerDevelopmentTest.php` | 9 | gap karier, rekomendasi, workflow, resource per peran |
| `tests/Feature/DatabaseSeederTest.php` | 1 | master data dan idempotensi seeder |
| `tests/Feature/DutyAttendanceTest.php` | 20 | jarak, radius, waktu, foto, idempotensi, HR verification, notification |
| `tests/Feature/DutyTripManagementTest.php` | 7 | create, cancel, ownership, visibility |
| `tests/Feature/ExampleTest.php` | 3 | root response, login UI, validasi Indonesia |
| `tests/Feature/FilamentAccessTest.php` | 15 | login, panel, resource, role, master data |
| `tests/Feature/FlowTest.php` | 1 | alur SDM lintas modul |
| `tests/Feature/MentoringWorkflowTest.php` | 7 | request, approve, reject, schedule, complete |
| `tests/Feature/MeritSystemTest.php` | 11 | formula, validasi, audit, lock, publication |
| `tests/Feature/OperationsReportTest.php` | 3 | laporan, export safety, foto privat |
| `tests/Feature/TrainingWorkflowTest.php` | 7 | request, approval, reject, resubmit, HR verification |
| `tests/Unit/ExampleTest.php` | 1 | sanity test |
| `tests/Unit/SqliteBackupTest.php` | 1 | backup dan restore SQLite |

`tests/Unit/ExampleTest.php` hanya sanity test. Bukan cakupan fitur.

## 12. Traceability Matrix

Prioritas:

- `P0`: kegagalan merusak keamanan, data, atau alur utama;
- `P1`: fungsi inti gagal tetapi workaround mungkin ada;
- `P2`: fungsi pendukung, usability, atau kualitas operasional.

| ID | Area | Expected result | Prioritas | Status |
|---|---|---|---|---|
| AUTH-01 | Login aktif | Pengguna aktif masuk dan diarahkan ke panel peran | P0 | AUTO |
| AUTH-02 | Login nonaktif | Login ditolak | P0 | AUTO |
| AUTH-03 | Sesi pengguna nonaktif | Akses berikutnya dihentikan | P0 | AUTO |
| AUTH-04 | Isolasi panel | Peran tidak dapat membuka panel lain | P0 | AUTO |
| AUTH-05 | Isolasi record | Pengguna hanya melihat record dalam scope | P0 | AUTO |
| AUTH-06 | Direct URL | URL resource/record ilegal ditolak tanpa bocor data | P0 | PARTIAL |
| ORG-01 | Assignment organisasi | Unit, jabatan, manager, dan role harus konsisten | P0 | AUTO |
| ORG-02 | Deaktivasi manager | Manager dengan bawahan tidak dapat dinonaktifkan atau diubah role | P1 | AUTO |
| ORG-03 | Historical data | Master data yang dipakai tidak hard delete | P1 | AUTO |
| TRIP-01 | Create trip | Atasan membuat tugas hanya untuk bawahan langsung | P0 | AUTO |
| TRIP-02 | Edit/cancel | Hanya Atasan terkait dapat mengubah atau membatalkan tugas valid | P0 | AUTO |
| TRIP-03 | Cancel after attendance | Tugas dengan absensi tidak dapat dibatalkan | P0 | AUTO |
| TRIP-04 | Visibility | Pegawai melihat tugas sendiri; Atasan melihat bawahan | P0 | AUTO |
| TRIP-05 | Overlap | Dua tugas tumpang tindih mengikuti aturan produk | P1 | TBD |
| ATT-01 | Inside radius | Absensi dalam radius mendapat status sesuai waktu | P0 | AUTO |
| ATT-02 | Outside radius | Absensi luar radius menjadi `NeedsReview` | P0 | AUTO |
| ATT-03 | Late | Pengiriman setelah waktu tugas diklasifikasikan terlambat | P0 | AUTO |
| ATT-04 | Backdated | Absensi mundur menjadi `NeedsReview` | P0 | AUTO |
| ATT-05 | Before start | Absensi sebelum tugas ditolak dan foto dibersihkan | P0 | AUTO |
| ATT-06 | Future clock | Selisih jam kecil ditoleransi | P1 | AUTO |
| ATT-07 | Idempotency | Tugas dan tanggal sama hanya menghasilkan satu record | P0 | AUTO |
| ATT-08 | Multi-day | Tugas multi-hari menerima satu absensi per tanggal tangkap | P0 | AUTO |
| ATT-09 | Ownership | Pegawai tidak dapat absen untuk tugas orang lain | P0 | AUTO |
| ATT-10 | HR verification | Hanya HR dapat memverifikasi `NeedsReview` | P0 | AUTO |
| ATT-11 | Private photo | Hanya pengguna berwenang dapat membuka foto | P0 | AUTO |
| ATT-12 | Camera permission | UI menangani izin diterima, ditolak, dan kamera tidak tersedia | P1 | MANUAL |
| ATT-13 | GPS permission | UI menangani izin diterima, ditolak, timeout, dan tidak tersedia | P1 | MANUAL |
| ATT-14 | Real GPS/radius | Jarak dan status cocok pada perangkat fisik | P0 | MANUAL |
| ATT-15 | Poor accuracy | Perilaku sesuai aturan produk | P1 | AUTO, TBD |
| ATT-16 | Offline | Pengiriman offline aman dan idempotent | P1 | TBD |
| ATT-17 | Retention | Foto dan koordinat dihapus sesuai kebijakan | P1 | TBD |
| ATT-18 | Status history | Status awal tetap dapat diaudit setelah verifikasi | P1 | TBD |
| KPI-01 | Period weights | Bobot komponen merit harus berjumlah 100% | P0 | AUTO |
| KPI-02 | Indicator weights | Total indikator tidak melebihi aturan period | P0 | AUTO |
| KPI-03 | Target/value | Target positif dan capaian tidak negatif | P0 | AUTO |
| KPI-04 | Assignment | Atasan hanya mengelola KPI bawahan | P0 | PARTIAL |
| REV-01 | Reviewer relation | Review hanya untuk hubungan yang sah | P0 | AUTO |
| REV-02 | Immutability | Review terkirim tidak dapat diubah atau dihapus | P0 | AUTO |
| MERIT-01 | Formula | Komponen, total, dan estimasi bonus dihitung benar | P0 | AUTO |
| MERIT-02 | Recalculate | Hasil belum diverifikasi dapat dihitung ulang | P1 | AUTO |
| MERIT-03 | Manager verification | Hanya Atasan terkait dapat memverifikasi tahap pertama | P0 | AUTO |
| MERIT-04 | HR publication | Publikasi terjadi setelah verifikasi HR | P0 | AUTO |
| MERIT-05 | Visibility | Pegawai hanya melihat hasil terpublikasi | P0 | AUTO |
| MERIT-06 | Lock | Input dan hasil terverifikasi/terpublikasi terkunci | P0 | AUTO |
| MERIT-07 | Audit breakdown | Rincian memakai KPI history dan review immutable | P1 | AUTO |
| MERIT-08 | Missing component | Formula tanpa data mengikuti keputusan produk | P0 | TBD |
| MERIT-09 | Discipline source | Nilai Valid/Late/duty trip mengikuti keputusan produk | P0 | TBD |
| CAREER-01 | Gap analysis | Gap menghasilkan rekomendasi training/mentoring | P1 | AUTO |
| TRAIN-01 | Employee request | Pegawai membuat pengajuan miliknya | P0 | AUTO |
| TRAIN-02 | Manager approval | Hanya Atasan terkait menyetujui/menolak | P0 | AUTO |
| TRAIN-03 | HR verification | HR memverifikasi setelah approval Atasan | P0 | AUTO |
| TRAIN-04 | Resubmit | Pengajuan ditolak dapat diajukan ulang sesuai rule | P1 | AUTO |
| TRAIN-05 | Invalid transition | Transition ilegal ditolak | P0 | AUTO |
| TRAIN-06 | Delegation | Delegate yang sah melihat dan memproses approval | P0 | GAP |
| MENTOR-01 | Employee request | Pegawai mengajukan mentoring dengan tanggal valid | P0 | AUTO |
| MENTOR-02 | Manager action | Atasan terkait approve, reject, schedule, complete | P0 | AUTO |
| MENTOR-03 | Invalid transition | Pengguna/status salah tidak dapat memproses | P0 | AUTO |
| MENTOR-04 | Delegation | Delegate yang sah melihat dan memproses mentoring | P0 | GAP |
| REPORT-01 | HR access | Hanya HR mengakses laporan | P0 | AUTO |
| REPORT-02 | Scope/filter | Periode dan filter membatasi semua data terkait | P1 | AUTO |
| REPORT-03 | Export safety | Nilai berbahaya tidak menjadi spreadsheet formula | P0 | AUTO |
| REPORT-04 | Format output | CSV, XLSX, dan PDF dapat dibuka serta isinya konsisten | P1 | PARTIAL |
| NOTIF-01 | Attendance review | Hanya HR aktif menerima notification | P1 | AUTO |
| NOTIF-02 | Workflow notification | Penerima dan isi notification sesuai transition | P1 | PARTIAL |
| NOTIF-03 | Queue worker | Notification queued diproses sekali tanpa duplikasi | P1 | GAP |
| SCHED-01 | Escalation | Approval lewat tiga hari menghasilkan notification tepat | P1 | GAP |
| SCHED-02 | KPI reminder | Reminder dikirim pada target dan kondisi tepat | P1 | GAP |
| SCHED-03 | Monthly merit | Calculate lalu report berjalan dalam urutan aman | P0 | GAP |
| AUDIT-01 | KPI changes | Nilai lama dan baru tersimpan | P1 | AUTO |
| AUDIT-02 | Workflow activity | Semua transition penting tercatat tanpa runtime error | P0 | GAP |
| BACKUP-01 | SQLite | Backup valid dan dapat direstore | P1 | AUTO |
| BACKUP-02 | MySQL | `mysqldump` sukses dan hasil dapat direstore | P0 | MANUAL |
| SEED-01 | Seeder | Seeder idempotent dan hanya membuat data bootstrap | P1 | AUTO |
| UI-01 | Responsive | Login, dashboard, form, table, dan attendance usable di mobile | P1 | PARTIAL |
| UI-02 | Accessibility | Keyboard, label, focus, contrast, dan error dapat dipahami | P1 | MANUAL |
| UI-03 | Language | Label dan validasi utama memakai Bahasa Indonesia konsisten | P2 | PARTIAL |
| MAP-01 | Map load | Peta dan Places bekerja dengan API key valid | P1 | MANUAL |
| MAP-02 | Map failure | Form memberi fallback/pesan saat API gagal | P1 | GAP |

## 13. Skenario Manual Wajib

### 13.1 Login dan panel

1. Login sebagai HR, Atasan, dan Pegawai.
2. Pastikan redirect dan navigation sesuai peran.
3. Salin URL panel lain dan buka langsung.
4. Nonaktifkan akun pada sesi lain, lalu refresh sesi pengguna.
5. Pastikan akses ditolak tanpa menampilkan data terlarang.

Expected result: tidak ada role bypass, record leak, atau navigation menyesatkan.

### 13.2 Perjalanan dinas dan peta

1. Login sebagai Atasan.
2. Buat tugas bagi bawahan menggunakan Map Picker.
3. Cari lokasi melalui Places.
4. Geser pin dan ubah radius.
5. Simpan, buka ulang, dan bandingkan koordinat.
6. Uji API key kosong, salah, atau request diblokir.
7. Uji viewport mobile.

Expected result: lokasi konsisten, error dapat dipahami, dan form tetap aman.

### 13.3 Absensi pada perangkat fisik

1. Buka tugas aktif sebagai Pegawai.
2. Tolak izin kamera; ulangi setelah izin diberikan.
3. Tolak izin lokasi; ulangi setelah izin diberikan.
4. Ambil foto dan lokasi di dalam radius.
5. Kirim dua kali cepat atau refresh setelah submit.
6. Ulangi dari luar radius.
7. Uji koneksi lambat dan putus saat upload.
8. Buka foto sebagai Pegawai pemilik, Atasan terkait, HR, dan pengguna asing.
9. Periksa layout pada orientasi portrait.

Expected result:

- hanya satu record per tugas/tanggal;
- status sesuai data;
- tombol memberi feedback selama submit;
- foto gagal tidak tertinggal;
- pengguna asing mendapat penolakan tanpa mengetahui path file.

### 13.4 Merit

1. HR membuat periode dan indikator.
2. Atasan mengisi KPI dan review.
3. Hitung merit.
4. Pastikan Pegawai belum melihat hasil.
5. Verifikasi sebagai Atasan, lalu HR.
6. Pastikan Pegawai melihat hasil terpublikasi.
7. Coba ubah periode, indikator, KPI, dan review.
8. Bandingkan breakdown dengan sumber data.

Expected result: formula benar, publication berurutan, dan data terpublikasi immutable.

### 13.5 Training dan mentoring

1. Pegawai membuat pengajuan.
2. Atasan lain mencoba memproses.
3. Atasan terkait approve/reject.
4. Pegawai mencoba resubmit pada status valid dan tidak valid.
5. HR memverifikasi training.
6. Atasan menjadwalkan dan menyelesaikan mentoring.
7. Bila delegation diaktifkan, ulangi sebagai delegate.

Expected result: hanya actor dan transition sah yang berhasil.

### 13.6 Laporan dan export

1. HR membuka laporan dengan dataset lintas periode.
2. Terapkan filter tanggal, unit, dan pegawai.
3. Bandingkan jumlah tampilan, CSV, XLSX, dan PDF.
4. Masukkan nilai berawalan `=`, `+`, `-`, atau `@`.
5. Buka hasil pada spreadsheet.
6. Coba URL laporan sebagai Atasan dan Pegawai.

Expected result: scope konsisten, file dapat dibuka, dan formula tidak dieksekusi.

### 13.7 Queue dan scheduler

1. Gunakan database queue.
2. Jalankan queue worker.
3. Buat event yang menghasilkan notification.
4. Pastikan job diproses sekali.
5. Simulasikan retry.
6. Jalankan setiap scheduler command pada data yang memenuhi dan tidak memenuhi syarat.
7. Periksa penerima, duplikasi, failure, dan activity log.

Expected result: command idempotent atau aman diulang, penerima tepat, failure terlihat.

### 13.8 Backup dan restore

1. Isi database MySQL dengan marker data.
2. Jalankan command backup.
3. Pastikan file tersimpan pada private storage.
4. Restore ke database test baru.
5. Jalankan integrity check dan cari marker data.
6. Uji retention tanpa menghapus backup yang masih dibutuhkan.

Expected result: backup bukan hanya berhasil dibuat, tetapi benar-benar dapat direstore.

## 14. Security Test Checklist

### 14.1 Authentication dan authorization

- akses tanpa login;
- role salah;
- record milik pengguna lain;
- ID valid tetapi di luar scope;
- akun dinonaktifkan setelah login;
- endpoint langsung tanpa melewati navigation;
- bulk action dan export;
- private photo path guessing.

### 14.2 Input dan upload

- file bukan gambar;
- MIME dan extension tidak cocok;
- file melebihi 5 MB;
- nama file berbahaya;
- payload koordinat kosong, string, `NaN`, atau di luar range;
- timestamp terlalu lama atau masa depan;
- spreadsheet formula injection;
- HTML/script pada field teks;
- parameter filter tidak valid.

### 14.3 Consistency dan race

- double click submit;
- dua tab mengubah record sama;
- verification bersamaan;
- calculate merit bersamaan;
- retry queued notification;
- unique constraint tetap menjadi pertahanan terakhir;
- transaction rollback membersihkan file dan data parsial.

## 15. Performance Smoke

Target angka final menunggu volume pengguna. Baseline sementara untuk lingkungan lokal stabil:

| Area | Pemeriksaan sementara |
|---|---|
| Login/panel | Tidak ada error dan response konsisten pada 10 request berurutan |
| Table | Halaman tetap usable pada minimal 1.000 record dummy |
| HR attendance alert | Query count tidak tumbuh linear terhadap jumlah record |
| Report | Filter dan export dataset 1.000 record selesai tanpa timeout |
| Photo | Upload maksimal 5 MB tidak menyebabkan memory error |
| Merit batch | Seluruh Pegawai dummy dihitung tanpa duplicate result |

Angka waktu response tidak menjadi release gate sampai hardware dan target SLA disepakati.

## 16. Accessibility dan Usability

Periksa:

- seluruh field memiliki label;
- pesan validasi menunjuk masalah dan cara memperbaiki;
- focus terlihat;
- urutan Tab logis;
- aksi penting dapat dipakai tanpa mouse, kecuali kamera/peta yang memerlukan alternatif jelas;
- dialog dapat ditutup via keyboard;
- warna bukan satu-satunya pembeda status;
- kontras teks terbaca;
- tombol submit tidak memungkinkan pengiriman ganda;
- tabel penting tetap dapat dibaca pada mobile;
- status dan istilah konsisten dalam Bahasa Indonesia.

## 17. Defect Management

### 17.1 Severity

| Severity | Definisi | Contoh |
|---|---|---|
| 1 — Critical | Data bocor, hilang, rusak, atau seluruh aplikasi tidak dapat dipakai | Role bypass, foto privat publik, migration gagal |
| 2 — High | Alur utama gagal tanpa workaround aman | Absensi gagal, merit salah, approval ilegal |
| 3 — Medium | Fungsi pendukung gagal atau ada workaround | Filter salah, notification terlambat |
| 4 — Low | Masalah visual, teks, atau usability kecil | Spacing, label tidak konsisten |

### 17.2 Isi laporan defect

- ID dan judul;
- environment;
- akun/peran;
- precondition dan data;
- langkah reproduksi;
- expected result;
- actual result;
- bukti screenshot/log secukupnya;
- severity;
- commit atau versi;
- status retest.

Defect yang sudah diperbaiki perlu regression test otomatis bila root cause dapat diuji stabil.

## 18. Risiko dan Gap Aktif

### 18.1 Risiko implementasi

- workflow escalation memakai `activity()`; dependency/helper perlu dipastikan tersedia sebelum jalur ini dipakai;
- delegation ada pada model training/mentoring, tetapi visibility dan aksi resource belum sepenuhnya membuktikan delegate dapat bekerja;
- link edit mentoring perlu diuji terhadap page yang benar-benar terdaftar;
- query laporan controller dan command terduplikasi sehingga hasil dapat berbeda;
- CSV memakai dependency transitif;
- CI belum memiliki satu matrix resmi dan belum membangun frontend;
- static analysis belum menjadi quality gate.

### 18.2 Risiko cakupan test

- mayoritas test memakai SQLite, sedangkan runtime utama MySQL;
- queue `sync` tidak menguji worker, retry, timeout, dan failed jobs;
- mail `array` tidak menguji rendering dan pengiriman aktual;
- tidak ada browser automation penuh;
- kamera, GPS, Places, dan jaringan nyata hanya dapat dipercaya setelah test perangkat;
- backup MySQL belum dibuktikan lewat restore otomatis;
- concurrency nyata belum diuji.

### 18.3 Keputusan produk yang memblokir expected result

- absensi harian atau hanya perjalanan dinas;
- aturan tugas multi-hari dan akhir pekan;
- batas waktu absensi terlambat;
- sumber waktu perangkat atau server;
- batas akurasi GPS;
- treatment luar radius dan terlambat;
- aksi HR: valid, tolak, atau batalkan verifikasi;
- face verification, mock location, dan offline attendance;
- retention foto dan koordinat;
- overlap tugas;
- history status absensi;
- formula komponen merit tanpa data;
- treatment attendance `Late` pada discipline score;
- arti estimasi bonus;
- approval delegation dan escalation.

Setelah keputusan dibuat, ubah item `TBD` menjadi expected result eksplisit dan tambahkan test.

## 19. Urutan Eksekusi

### 19.1 Setiap perubahan

```bash
vendor/bin/pint --dirty
php artisan test
git diff --check
```

Tambahkan:

```bash
npm run build
```

bila frontend atau asset berubah.

### 19.2 Sebelum demo

1. Jalankan seluruh test otomatis.
2. Jalankan migration dan seeder pada database lokal bersih.
3. Smoke test tiga peran.
4. Jalankan alur perjalanan dinas dan absensi pada perangkat fisik.
5. Jalankan alur merit sampai publikasi.
6. Jalankan training dan mentoring.
7. Verifikasi laporan dan semua format export.
8. Jalankan queue/scheduler smoke.
9. Buat dan restore backup.
10. Catat hasil, defect, GAP, dan keputusan TBD.

## 20. Test Report

Setiap siklus demo/rilis mencatat:

| Field | Isi |
|---|---|
| Versi/commit | Commit yang diuji |
| Tanggal | Waktu eksekusi |
| Environment | PHP, DB, browser, perangkat |
| Automated | Passed, failed, skipped |
| Manual | Passed, failed, blocked, not run |
| Defect | Daftar defect dan severity |
| Gap/TBD | Test yang belum dapat dijalankan |
| Keputusan | Go, conditional go, atau no-go |
| Penguji | Nama anggota tim |

## 21. Ownership

Untuk skala proyek mata kuliah:

- developer fitur menulis dan menjalankan test otomatis;
- anggota tim lain melakukan minimal satu manual review untuk alur utama;
- pemilik product spec memutuskan expected result item TBD;
- seluruh tim menyetujui hasil test sebelum demo.

Tidak diperlukan struktur QA terpisah. Yang diperlukan: cakupan jelas, bukti test, defect tercatat, dan keputusan produk tidak dibiarkan ambigu.
