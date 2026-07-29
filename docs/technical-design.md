# Technical Design — ABL Sistem SDM

> - Status: Draft v0.1
> - Baseline implementasi: 29 Juli 2026
> - Jenis dokumen: *as-built technical design*
> - Product baseline: [product-spec.md](product-spec.md)

## 1. Tujuan Dokumen

Dokumen ini menjelaskan bentuk teknis aplikasi yang berjalan saat ini. Fokusnya:

- arsitektur dan batas komponen;
- alur request serta proses latar belakang;
- model data dan state transition;
- authorization, keamanan, dan konsistensi data;
- integrasi eksternal, storage, dan deployment;
- risiko teknis yang perlu diputuskan sebelum pengembangan berikutnya.

Dokumen ini bukan rancangan ideal baru. Bagian **As-built** berasal dari kode aktif. Bagian **TBD** atau **Technical debt** belum dianggap selesai.

Jika dokumen teknis lama bertentangan dengan dokumen ini, kode aktif menjadi baseline.

## 2. Ringkasan Arsitektur

ABL adalah **modular monolith** berbasis Laravel. Seluruh modul berjalan dalam satu aplikasi, satu database, dan satu deployment unit.

Antarmuka utama memakai tiga panel Filament:

- `/pegawai` untuk Pegawai;
- `/atasan` untuk Atasan;
- `/hr` untuk Admin SDM/HR.

Route Laravel biasa dipakai untuk login, pengambilan absensi, foto privat, dan laporan HR.

```text
Browser
├── Filament + Livewire
│   ├── Portal Pegawai
│   ├── Portal Atasan
│   └── Portal HR
├── Blade + vanilla JavaScript
│   ├── Login
│   ├── Pengambilan absensi
│   └── Laporan HR
└── Google Maps JavaScript API

Laravel Application
├── Route dan middleware
├── Filament resources, pages, forms, tables, widgets
├── Controllers
├── Services dan domain rules
├── Eloquent models
├── Notifications dan mail
├── Console commands dan scheduler
└── Reports dan exports

Infrastructure
├── MySQL
├── Database queue, cache, session, notifications
├── Private local storage
├── Mail transport
└── Sentry, bila DSN dikonfigurasi
```

### 2.1 Karakter arsitektur

- **Server-rendered web application**: tidak ada public REST API.
- **Role-specific portal**: panel terpisah, model dan resource dipakai bersama.
- **Shared database**: semua modul memakai satu schema.
- **Synchronous domain workflow**: perubahan status terjadi saat action diproses.
- **Asynchronous notification**: notification yang queued membutuhkan queue worker.
- **Scheduled automation**: perhitungan merit, reminder, laporan, eskalasi, dan backup dijalankan scheduler.

## 3. Technology Stack

| Area | Implementasi aktif |
|---|---|
| Bahasa | PHP `^8.2`; runtime lokal saat audit `8.3.6` |
| Framework | Laravel `12.64.0` |
| Admin UI | Filament `5.6.8` |
| Reactive UI | Livewire `4.3.3` |
| Template | Blade |
| Frontend | Vanilla JavaScript, Vite `7`, Tailwind CSS `4` |
| Peta | Google Maps JavaScript API dan Places |
| Database default | MySQL; image lokal `mysql:8.4.10` |
| Database test | SQLite in-memory melalui PHPUnit |
| Queue | Database |
| Cache | Database |
| Session | Database |
| File storage | Laravel `local` disk |
| CSV | `league/csv` `9.28.0` |
| XLSX | OpenSpout `4` |
| PDF | `barryvdh/laravel-dompdf` `3` |
| Monitoring | Sentry Laravel SDK `4`; tidak aktif tanpa DSN |
| Testing | PHPUnit `11`, Laravel feature tests |
| Formatting | Laravel Pint |
| CI | GitHub Actions |

### 3.1 Catatan dependency

- `league/csv` dipakai langsung oleh `HrReportController`, tetapi tidak tercantum sebagai direct dependency pada `composer.json`. Paket saat ini tersedia secara transitif.
- Tidak ada package face recognition, Web Push, atau PWA pada dependency aktif.
- Tidak ada dependency frontend untuk peta; Google Maps dimuat langsung dari script eksternal.

## 4. Struktur Kode dan Tanggung Jawab

| Lokasi | Tanggung jawab |
|---|---|
| `routes/web.php` | Login, absensi, foto privat, laporan HR |
| `routes/console.php` | Scheduler dan command backup SQLite |
| `app/Providers/Filament` | Registrasi panel, resource, widget, middleware |
| `app/Filament/Resources` | CRUD, form, table, action, visibility, query scope UI |
| `app/Filament/Widgets` | Ringkasan dashboard per peran |
| `app/Http/Controllers` | Endpoint web non-Filament |
| `app/Http/Middleware` | Akun aktif dan penanganan halaman terlarang |
| `app/Models` | Relasi, casts, business guard, state transition |
| `app/Models/Concerns` | Helper transaksi workflow |
| `app/Services` | Logika lintas model atau perhitungan utama |
| `app/Support` | Helper stateless seperti kalkulasi jarak |
| `app/Enums` | Nilai role, status, dan label UI |
| `app/Notifications` | Notifikasi database dan email |
| `app/Console/Commands` | Otomasi merit, laporan, reminder, approval, backup |
| `resources/views` | Login, absensi, laporan, custom Filament component |
| `database/migrations` | Schema dan constraint |
| `database/seeders` | Master organisasi, akun awal, approval chain |
| `tests/Unit` | Helper teknis terisolasi |
| `tests/Feature` | Workflow, authorization, UI, report, dan integrasi model |

### 4.1 Pembagian layer aktual

Pembagian layer tidak sepenuhnya kaku:

- Filament resource mengatur authorization UI dan action.
- Model menyimpan invariant serta state transition.
- Service dipakai ketika logika melibatkan perhitungan atau beberapa aggregate.
- Controller mengatur validasi HTTP, file lifecycle, dan response.
- Database constraint menjadi lapisan terakhir untuk uniqueness dan foreign key.

Tidak ada repository layer, DTO layer, atau interface service. Eloquent dipakai langsung. Ini sesuai ukuran aplikasi saat ini.

## 5. Entry Point dan Request Lifecycle

### 5.1 Bootstrap aplikasi

`bootstrap/app.php`:

- mendaftarkan `routes/web.php`;
- mendaftarkan `routes/console.php`;
- menyediakan health endpoint `/up`;
- memberi alias middleware `active` kepada `EnsureUserIsActive`;
- memakai exception handler default Laravel.

### 5.2 Login

```text
GET/POST /login
1. AuthenticatedSessionController memvalidasi email dan password.
2. Query login menambahkan syarat is_active = true.
3. Session diregenerasi setelah login berhasil.
4. UserRole menentukan redirect ke /pegawai, /atasan, atau /hr.
5. User::canAccessPanel memeriksa role dan status aktif.
```

Login dibatasi lima percobaan per menit.

### 5.3 Request Filament

```text
Browser
1. Panel provider menentukan panel dan resource terdaftar.
2. Filament auth middleware memastikan session valid.
3. User::canAccessPanel membatasi panel.
4. Resource::can* membatasi jenis aksi.
5. getEloquentQuery atau scopeVisibleTo membatasi baris data.
6. Form/action memvalidasi input.
7. Model atau service memeriksa invariant.
8. Eloquent menulis ke database.
9. Notification dan ActivityLog dicatat bila diperlukan.
```

`RolePanelProvider` mengaktifkan:

- database transaction untuk operasi Filament;
- database notification;
- polling notifikasi setiap 30 detik;
- unsaved changes alert;
- shared profile page;
- middleware penanganan halaman terlarang.

### 5.4 Business exception

Domain rule gagal dengan `BusinessRuleException`.

Untuk Filament Page, listener Livewire pada `AppServiceProvider`:

1. menangkap `BusinessRuleException`;
2. menampilkan notification `Tindakan tidak dapat diproses`;
3. menghentikan propagation agar rule bisnis tidak menjadi error 500.

Untuk endpoint absensi, controller menangkap exception dan mengembalikan JSON `422`.

## 6. Panel dan UI Composition

### 6.1 Shared resource

Pegawai dan Atasan memakai resource yang sama untuk:

- dinas;
- absensi;
- KPI;
- penilaian;
- merit;
- kompetensi pegawai;
- target karier;
- katalog dan pengajuan pelatihan;
- mentoring.

Perbedaan tampilan dan data diatur melalui:

- navigation label berdasarkan role;
- `canCreate`, `canEdit`, `canDelete`, dan `canView`;
- `scopeVisibleTo`;
- kondisi `visible` pada action;
- daftar resource dan widget pada setiap panel provider.

### 6.2 HR-only resource

HR memiliki resource tambahan:

- pengguna;
- unit;
- jabatan;
- lokasi dinas;
- periode penilaian;
- indikator KPI;
- kamus kompetensi;
- standar kompetensi jabatan;
- activity log;
- approval chain;
- laporan lintas modul.

### 6.3 Custom browser UI

Halaman pengambilan absensi menggunakan Blade dan vanilla JavaScript:

- `navigator.geolocation.getCurrentPosition`;
- `navigator.mediaDevices.getUserMedia`;
- canvas untuk foto dan watermark;
- `FormData` dan `fetch` untuk submit;
- indikator online/offline.

Tidak ada IndexedDB queue, service worker, face recognition, atau mock-location detector pada alur aktif.

Komponen peta Filament memuat Google Maps JavaScript API dengan library Places memakai `GOOGLE_MAPS_API_KEY`.

## 7. Modul dan Komponen Utama

### 7.1 Organisasi dan akses

| Komponen | Fungsi |
|---|---|
| `User`, `Unit`, `Position` | Struktur organisasi dan hubungan Atasan |
| `UserRole` | Enum Pegawai, Atasan, HR |
| `UserResource`, `UnitResource`, `PositionResource` | Master data HR |
| `AuthenticatedSessionController` | Login dan redirect panel |
| `EnsureUserIsActive` | Memutus session user nonaktif |

Invariant organisasi berada pada event `User::saving`.

### 7.2 Perjalanan dan absensi

| Komponen | Fungsi |
|---|---|
| `DutyLocation` | Master titik dan radius |
| `DutyTrip` | Penugasan, snapshot lokasi, pembatalan, scope |
| `Attendance` | Record absensi dan verifikasi HR |
| `AttendanceController` | Halaman capture, submit, dan foto privat |
| `AttendanceRecorder` | Validasi, idempotency, jarak, status, log |
| `GeoDistance` | Haversine distance dalam meter |
| `DutyTripResource` | Pengelolaan dan monitoring tugas |
| `AttendanceResource` | Riwayat, monitoring, verifikasi |
| `TripAssigned` | Notifikasi penugasan |
| `AttendanceNeedsReview` | Notifikasi pemeriksaan HR |

#### Alur pencatatan absensi

```text
Browser mengambil GPS dan foto
1. AttendanceController memvalidasi request.
2. Foto disimpan ke private local disk.
3. AttendanceRecorder membuka DB transaction.
4. DutyTrip dikunci dengan lockForUpdate.
5. Duplicate attendance pada tanggal sama dikembalikan.
6. GeoDistance menghitung jarak.
7. Server membandingkan waktu perangkat dengan waktu server.
8. Status dipilih: Valid, Late, atau NeedsReview.
9. Attendance dan ActivityLog disimpan.
10. HR diberi notifikasi bila NeedsReview.
11. Controller menghapus foto baru bila terjadi error atau duplicate.
```

### 7.3 KPI dan merit

| Komponen | Fungsi |
|---|---|
| `ReviewPeriod` | Periode, bobot komponen, dasar bonus |
| `KpiIndicator` | Indikator dan bobot dalam periode |
| `EmployeeKpi` | Target serta capaian Pegawai |
| `PerformanceReview` | Penilaian skala 1–5 |
| `MeritResult` | Snapshot skor, verifikasi, publikasi |
| `MeritCalculator` | Formula KPI, disiplin, review, total, bonus |
| `CalculateMerit` | Batch perhitungan |
| `RemindKpi` | Pengingat KPI yang belum diisi |
| `MeritReadyForVerification` | Notifikasi kepada Atasan |
| `MeritPublished` | Notifikasi kepada Pegawai |

`MeritCalculator` memakai transaction dan `lockForUpdate` pada periode. Hasil yang sudah diverifikasi atau dipublikasikan tidak dapat dihitung ulang.

### 7.4 Pengembangan karier

| Komponen | Fungsi |
|---|---|
| `Competency` | Kamus kompetensi |
| `PositionCompetency` | Standar level per jabatan |
| `EmployeeCompetency` | Level aktual Pegawai |
| `CareerGoal` | Target jabatan |
| `CareerGapService` | Selisih level dan rekomendasi |
| `Training` | Katalog pelatihan |
| `TrainingRequest` | Workflow pengajuan dan rekomendasi |
| `Mentoring` | Workflow jadwal dan hasil mentoring |
| `HasWorkflow` | Transaction dan row lock untuk transition |
| `ApprovalChain` | Konfigurasi urutan role untuk eskalasi |

`TrainingRequest` dan `Mentoring` menyimpan transition di model. `HasWorkflow` mengambil ulang row menggunakan `lockForUpdate` sebelum mengubah status.

### 7.5 Laporan dan audit

| Komponen | Fungsi |
|---|---|
| `HrReportController` | Query laporan, filter, CSV, PDF, XLSX |
| `SendReport` | Laporan periodik melalui email |
| `ActivityLog` | Audit log polymorphic |
| `ActivityLogResource` | Tampilan read-only untuk HR |

CSV dan XLSX ditulis sebagai streamed response. PDF dirender melalui DomPDF.

## 8. Model Data

### 8.1 Kelompok tabel

| Domain | Tabel |
|---|---|
| Organisasi | `users`, `units`, `positions` |
| Operasional | `duty_locations`, `duty_trips`, `attendances` |
| Kinerja | `review_periods`, `kpi_indicators`, `employee_kpis`, `performance_reviews`, `merit_results` |
| Pengembangan | `competencies`, `position_competency`, `employee_competencies`, `career_goals`, `trainings`, `training_requests`, `mentorings` |
| Audit dan workflow | `activity_logs`, `approval_chains`, `notifications` |
| Laravel infrastructure | `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens` |

### 8.2 Relasi utama

```text
Unit
├── Positions
└── Users
    ├── manager: User
    ├── delegate: User
    ├── DutyTrips
    ├── EmployeeKpis
    ├── PerformanceReviews
    ├── MeritResults
    ├── EmployeeCompetencies
    ├── CareerGoal
    ├── TrainingRequests
    └── Mentorings

DutyLocation
└── DutyTrips
    └── Attendances

ReviewPeriod
├── KpiIndicators
├── EmployeeKpis
├── PerformanceReviews
└── MeritResults

Position
└── PositionCompetencies
    └── Competency
        ├── EmployeeCompetencies
        └── Trainings
```

### 8.3 Constraint penting

| Constraint | Tujuan |
|---|---|
| `users.email` unique | Satu akun per email |
| `users.employee_number` unique nullable | Satu nomor pegawai |
| `units.code` unique | Identitas unit |
| `positions(unit_id, name)` unique | Nama jabatan unik dalam unit |
| `attendances(duty_trip_id, employee_id, attendance_date)` unique | Satu absensi per Pegawai, tugas, dan tanggal |
| `kpi_indicators(review_period_id, name)` unique | Nama indikator unik per periode |
| `employee_kpis(kpi_indicator_id, employee_id)` unique | Satu KPI per indikator dan Pegawai |
| `performance_reviews(period, reviewer, reviewee, type)` unique | Satu penilaian untuk kombinasi tersebut |
| `merit_results(review_period_id, employee_id)` unique | Satu hasil merit per Pegawai dan periode |
| `position_competency(position_id, competency_id)` unique | Satu standar per kompetensi dan jabatan |
| `employee_competencies(user_id, competency_id)` unique | Satu level aktual per kompetensi |
| `career_goals.user_id` unique | Satu target karier per Pegawai |
| `training_requests(user_id, training_id)` unique | Satu record pelatihan per Pegawai |
| `approval_chains.module` unique | Satu konfigurasi chain per modul |

### 8.4 Denormalization dan snapshot

`DutyTrip` menyimpan salinan:

- nama lokasi;
- alamat;
- latitude;
- longitude;
- radius.

Perubahan master `DutyLocation` tidak mengubah tugas lama.

`MeritResult` menyimpan skor komponen, total, estimasi bonus, dan timestamp verifikasi. Hasil menjadi snapshot setelah verifikasi.

`ActivityLog` menyimpan `subject_type` dan `subject_id` agar dapat mencatat beberapa jenis model.

### 8.5 Delete strategy

- Data anak yang tidak bermakna tanpa parent banyak memakai cascade delete.
- Referensi Atasan memakai restrict delete pada beberapa workflow.
- Referensi verifier memakai null-on-delete.
- Penghapusan akun melalui panel dinonaktifkan.
- Beberapa master data masih menyediakan delete action; foreign key menjadi pelindung terakhir.

## 9. State dan Konsistensi

### 9.1 State machine

| Aggregate | State dan transition |
|---|---|
| DutyTrip | `Approved`; Atasan dapat mengubah menjadi `Cancelled` |
| Attendance | Dibuat sebagai `Valid`, `Late`, atau `NeedsReview`; HR dapat mengubah `NeedsReview` menjadi `Valid` |
| TrainingRequest | `PendingManager`, `Rejected`, `PendingHr`, `Approved`, `Completed` |
| Mentoring | `Pending`, `Rejected`, `Approved`, `Completed` |
| MeritResult | Calculated, manager verified, HR verified dan published; lifecycle disimpan lewat timestamp |

### 9.2 Transaction boundary

Explicit transaction dan row lock dipakai pada:

- pencatatan absensi;
- verifikasi absensi;
- perhitungan merit;
- verifikasi Atasan dan HR;
- transition pelatihan;
- transition mentoring;
- rekomendasi pelatihan Atasan.

Transaction mencoba ulang sampai tiga kali pada flow utama.

Filament juga dikonfigurasi memakai database transaction untuk action panel.

### 9.3 Idempotency

- Absensi mengembalikan record lama bila kombinasi tugas dan tanggal sudah ada.
- `MeritCalculator` memperbarui hasil lama selama belum diverifikasi.
- Seeder memakai `upsert` atau operasi idempotent.
- Database unique constraint melindungi request bersamaan.

## 10. Authorization dan Security

### 10.1 Lapisan authorization

1. `auth` middleware memastikan pengguna login.
2. `active` middleware memastikan akun tetap aktif.
3. `User::canAccessPanel` membatasi panel.
4. Resource `can*` membatasi operasi.
5. Query scope membatasi record.
6. Model/service memeriksa hubungan bisnis.
7. Database foreign key dan unique constraint melindungi data.

Authorization saat ini memakai role check dan query scope langsung. Tidak ada Laravel Policy terpisah.

### 10.2 Proteksi endpoint

- Login: `throttle:5,1`.
- Submit absensi: `throttle:10,1`.
- Semua POST memakai CSRF middleware.
- Request divalidasi memakai Laravel validator.
- Password disimpan melalui cast `hashed`.
- Session diregenerasi setelah login.

### 10.3 Foto dan data lokasi

- Foto disimpan pada disk `local`, bukan disk public.
- Foto hanya disajikan melalui controller terotorisasi.
- Pegawai dibatasi ke foto sendiri.
- Atasan dibatasi ke foto tugas yang dikelolanya.
- HR dapat melihat semua foto.

Retention foto dan koordinat belum ditentukan.

### 10.4 Export safety

CSV menambahkan apostrof pada nilai yang diawali karakter formula spreadsheet seperti `=`, `+`, `-`, `@`, tab, atau carriage return.

### 10.5 External script

Google Maps JavaScript dimuat dari domain Google. Production membutuhkan:

- HTTPS;
- API key;
- restriction berdasarkan domain dan API;
- Content Security Policy yang mengizinkan resource Google Maps bila CSP diterapkan.

## 11. Notification, Queue, dan Mail

Channel notification yang dipakai:

- database;
- email pada notification tertentu.

Database notification ditampilkan Filament dan dipolling setiap 30 detik.

Konfigurasi lokal:

- queue: database;
- mail: log.

Production membutuhkan queue worker aktif. Tanpa worker, queued notification tidak dikirim.

Notification utama:

- `TripAssigned`;
- `AttendanceNeedsReview`;
- `KpiDeadlineReminder`;
- `MeritReadyForVerification`;
- `MeritPublished`;
- `TrainingPending`;
- `MentoringPending`;
- `MentoringScheduled`.

## 12. Scheduler dan Background Process

| Jadwal | Command | Fungsi |
|---|---|---|
| Harian 02.00 | `backup:database` atau `db:backup` | Backup dan retention |
| Harian 06.00 | `approval:escalate` | Notifikasi approval lebih dari tiga hari |
| Harian 09.00 | `merit:remind-kpi` | Pengingat KPI belum lengkap |
| Tanggal 1, 00.05 | `merit:calculate` | Hitung hasil merit |
| Tanggal 1, 01.00 | `merit:send-report` | Kirim laporan HR |

Semua schedule memakai `withoutOverlapping`.

Server harus menjalankan:

```bash
php artisan schedule:run
php artisan queue:work
```

Scheduler biasanya dipanggil cron setiap menit. Queue worker harus dikelola process supervisor.

## 13. Storage dan Backup

### 13.1 Attendance photo

- Path logis: `attendance/...`
- Disk: `local`
- Akses: controller, bukan symlink public
- Maksimal upload: 5 MB
- Cleanup: foto baru dihapus jika business rule gagal, exception terjadi, atau request ternyata duplicate

### 13.2 Database backup

- MySQL: `mysqldump`, disimpan pada `storage/app/private/backups` melalui local disk.
- SQLite: online backup melalui `SqliteBackup`.
- Retention default: 14 file.
- SQLite dapat mengunggah backup ke cloud disk bila dikonfigurasi.

Restore otomatis belum tersedia. Verifikasi restore tetap proses operasional manual.

## 14. Configuration

Variabel utama:

| Variable | Kegunaan | Default contoh |
|---|---|---|
| `APP_ENV` | Environment aplikasi | `local` |
| `APP_URL` | Base URL | `http://localhost` |
| `APP_TIMEZONE` | Zona waktu | `Asia/Jakarta` |
| `DB_*` | Koneksi database | MySQL |
| `QUEUE_CONNECTION` | Driver queue | `database` |
| `CACHE_STORE` | Driver cache | `database` |
| `SESSION_DRIVER` | Driver session | `database` |
| `FILESYSTEM_DISK` | Default storage | `local` |
| `FILESYSTEM_CLOUD` | Backup cloud opsional | kosong |
| `MAIL_*` | Transport email | `log` |
| `GOOGLE_MAPS_API_KEY` | Maps dan Places | kosong |
| `ATTENDANCE_CLOCK_TOLERANCE_MINUTES` | Toleransi waktu perangkat | `15` |
| `BACKUP_KEEP` | Jumlah backup disimpan | `14` |
| `SENTRY_LARAVEL_DSN` | Error reporting | kosong |

Secret tidak boleh disimpan dalam repository.

## 15. Deployment Topology

Deployment minimum membutuhkan:

```text
Reverse proxy / web server
└── Laravel PHP application
    ├── Web process
    ├── Queue worker
    └── Scheduler

Shared dependencies
├── MySQL
├── Persistent private storage
├── Mail provider
└── Google Maps API
```

Local development memakai:

- MySQL melalui `compose.yaml`;
- PHP application pada host;
- Vite development server;
- queue listener;
- Laravel Pail.

`composer dev` menjalankan server, queue listener, log viewer, dan Vite secara bersamaan.

Jika aplikasi dijalankan pada lebih dari satu web instance, private attendance storage harus menjadi shared storage atau object storage. Local disk per instance tidak cukup.

## 16. Testing dan CI

### 16.1 Local test

`phpunit.xml` memakai:

- SQLite in-memory;
- queue sync;
- mail array;
- cache dan session array.

Test suite mencakup:

- authorization tiga panel;
- perjalanan dinas dan absensi;
- formula serta publikasi merit;
- pelatihan dan mentoring;
- karier dan kompetensi;
- laporan serta foto privat;
- seeder;
- backup SQLite.

### 16.2 GitHub Actions

Repository memiliki dua workflow:

- `test.yml`: PHP 8.3 dan SQLite;
- `tests.yml`: PHP 8.2 dan MySQL 8.4.

Workflow MySQL menjalankan Pint dan test. Static analysis dipanggil secara non-blocking.

Frontend build dan browser smoke test belum menjadi bagian CI.

## 17. Technical Debt dan Risiko

### 17.1 Prioritas tinggi

1. **Eskalasi approval memanggil helper tanpa dependency**

   `EscalateApprovals` dan `HasWorkflow` memanggil `activity()`, tetapi package `spatie/laravel-activitylog` tidak terpasang. Path ini berisiko gagal ketika record benar-benar dieskalasi atau helper trait dipakai.

2. **Delegasi tidak konsisten antara domain dan UI**

   Model pelatihan dan mentoring mendukung actor delegasi. Resource query dan visibility masih membatasi record ke `manager_id` asli. Delegate kemungkinan tidak dapat menemukan atau mengeksekusi action dari panel.

3. **Widget approval memakai page yang tidak terdaftar**

   `ManagerPendingApprovalsTable` membentuk URL `MentoringResource` page `edit`, sedangkan resource mentoring hanya mendaftarkan page `index`.

4. **Dokumen lama tidak sesuai implementasi**

   Dokumen lama menyebut PWA, face verification, mock-location detection, Web Push, Leaflet, dan offline queue. Komponen tersebut tidak ada pada flow aktif.

### 17.2 Prioritas menengah

1. Authorization tersebar di resource, query scope, controller, dan model. Perubahan role rule harus diperiksa di beberapa tempat.
2. Query laporan diduplikasi antara `HrReportController` dan `SendReport`.
3. `league/csv` dipakai sebagai dependency transitif, bukan direct dependency.
4. Dua workflow CI menjalankan matrix berbeda dan berpotensi memberi hasil tidak konsisten.
5. Static analysis CI tidak blocking dan package PHPStan tidak tercantum sebagai direct dev dependency.
6. CI belum menjalankan `npm run build`.
7. PDF report dirender dalam memory; volume data besar belum diuji.
8. Retention foto, koordinat, activity log, dan notification belum ditetapkan.
9. Sentry tersedia tetapi tidak aktif tanpa konfigurasi DSN.

### 17.3 Risiko desain produk yang memengaruhi teknis

- Waktu absensi berasal dari perangkat lalu hanya dibandingkan dengan server.
- Akurasi GPS disimpan tetapi tidak dipakai menentukan status.
- Absensi terlambat tidak mempunyai batas pengiriman akhir.
- HR hanya dapat mengubah `NeedsReview` menjadi `Valid`.
- Nilai disiplin hanya menghitung absensi perjalanan dinas berstatus Valid.
- Approval chain saat ini menentukan target notifikasi eskalasi, bukan state machine dinamis.

## 18. Keputusan Teknis TBD

1. Apakah authorization akan tetap memakai resource/model checks atau dipusatkan ke Laravel Policy?
2. Apakah attendance photo tetap local disk atau pindah ke object storage?
3. Apakah queue, cache, dan session database cukup untuk target beban?
4. Apakah offline attendance benar-benar dibutuhkan?
5. Jika offline dibutuhkan, bagaimana signing payload dan pencegahan manipulasi waktu?
6. Apakah Google Maps tetap provider peta?
7. Apakah face verification atau liveness masuk scope?
8. Apakah status absensi perlu event/history terpisah agar status awal tidak hilang setelah verifikasi?
9. Apakah approval chain perlu menjadi workflow engine atau cukup konfigurasi notifikasi?
10. Apakah CI perlu satu matrix resmi: PHP 8.2/8.3 dan MySQL/SQLite?
11. Apakah static analysis akan menjadi quality gate?
12. Berapa target volume pengguna, transaksi, dan file foto?

## 19. Aturan Perubahan

Sebelum mengubah modul:

1. perbarui requirement pada `product-spec.md`;
2. tentukan perubahan state, authorization, dan data;
3. periksa migration serta backward compatibility;
4. ubah shared domain path, bukan hanya satu tampilan;
5. tambah test pada invariant atau transition baru;
6. perbarui dokumen ini bila arsitektur berubah.

