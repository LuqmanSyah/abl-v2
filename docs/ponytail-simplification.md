# Usulan Penyederhanaan ABL

## Ringkasan

Versi paling kecil yang masih mempertahankan identitas ABL:

1. satu panel aplikasi untuk semua peran;
2. organisasi dasar: pengguna, unit, jabatan, dan atasan langsung;
3. perjalanan dinas dengan absensi harian berfoto dan geofence;
4. KPI sederhana dengan perhitungan merit tetap;
5. satu modul pengembangan untuk target karier, pelatihan, dan mentoring;
6. satu laporan HR yang dapat diekspor ke CSV;
7. audit hanya untuk aksi penting.

Targetnya bukan sistem HR enterprise. Targetnya aplikasi demonstrasi yang alurnya mudah dijelaskan, diuji, dan dirawat.

Audit dilakukan terhadap kondisi repository saat ini. Baseline aplikasi:

- 9.318 baris PHP di `app/`;
- 20 domain Filament dengan 91 file resource;
- 15 widget dashboard;
- 21 model;
- 8 notification class;
- 5 scheduled/console command;
- tiga panel terpisah: Pegawai, Atasan, dan HR.

## Asumsi Penyederhanaan

- ABL dipakai untuk pengembangan/demonstrasi, bukan pengganti seluruh HRIS organisasi.
- Perjalanan dinas membutuhkan satu bukti kehadiran per tanggal kalender selama jadwal.
- Penilaian 360 derajat, approval bertingkat, delegasi, dan laporan terjadwal belum menjadi kebutuhan inti.
- Semua pengguna memakai timezone aplikasi yang sama.
- Fitur keamanan, pembatasan akses, validasi, dan pencegahan duplikasi tidak boleh dipangkas.

## Bentuk Web Setelah Disederhanakan

```text
/app
├── Dashboard
├── Organisasi
│   ├── Pegawai
│   ├── Unit
│   └── Jabatan
├── Operasional
│   ├── Perjalanan Dinas
│   └── Absensi
├── Kinerja
│   ├── Periode
│   ├── KPI
│   └── Merit
├── Pengembangan
│   ├── Rencana Pengembangan
│   └── Pengajuan Pengembangan
└── Laporan
    ├── Ringkasan SDM
    └── Audit Penting
```

Menu dan data tetap berbeda menurut peran. Perbedaannya ditentukan oleh policy/query scope, bukan panel dan route terpisah.

## Penyederhanaan per Fitur

### 1. Login, Profil, dan Panel

**Sekarang**

- panel `/pegawai`, `/atasan`, dan `/hr`;
- empat provider panel, custom controller login, custom halaman login, redirect berdasarkan role, serta middleware panel;
- setiap panel mendaftarkan ulang resource dan widget.

**Versi sederhana**

- satu panel `/app`;
- gunakan login dan profil bawaan Filament;
- `User::canAccessPanel()` cukup memastikan pengguna aktif;
- role hanya mengatur menu, query data, dan izin aksi;
- satu dashboard dengan statistik sesuai role.

**Tetap wajib**

- session regeneration saat login;
- pengguna nonaktif ditolak;
- Pegawai hanya melihat data sendiri;
- Atasan hanya melihat bawahan langsung;
- HR dapat melihat seluruh data yang relevan.

**Dihapus**

- `EmployeePanelProvider`, `ManagerPanelProvider`, dan `HrPanelProvider`;
- redirect berdasarkan URL role;
- duplikasi registrasi resource;
- warna portal per role.

### 2. Dashboard

**Sekarang**

Terdapat 15 widget. Banyak widget menampilkan potongan data yang sudah tersedia pada halaman resource.

**Versi sederhana**

Satu widget statistik dinamis:

- Pegawai: dinas aktif, KPI aktif, pengajuan tertunda;
- Atasan: tugas aktif, KPI belum diisi, approval tertunda;
- HR: absensi perlu diperiksa, merit belum dipublikasi, pengajuan aktif.

Setiap angka menjadi tautan ke daftar terfilter. Tabel dashboard dihapus karena menduplikasi menu utama.

**Dihapus**

- seluruh table widget;
- statistik berulang per role;
- polling notifikasi 30 detik.

### 3. Organisasi

**Sekarang**

`User`, `Unit`, dan `Position` sudah cukup mewakili struktur organisasi, tetapi `User` juga membawa delegasi dan banyak relasi modul tambahan.

**Versi sederhana**

- `User`: nama, email, password, role, unit, jabatan, atasan, nomor pegawai, status aktif;
- `Unit`: nama;
- `Position`: nama, unit, level;
- HR mengelola ketiganya;
- avatar opsional memakai inisial bawaan, bukan provider khusus.

**Dihapus**

- `delegate_id` dan seluruh logika delegasi;
- `avatar_url` bila unggah avatar bukan requirement;
- field telepon bila tidak dipakai pada proses lain;
- resource konfigurasi rantai persetujuan.

### 4. Perjalanan Dinas

**Sekarang**

Perjalanan dinas mendukung master lokasi, snapshot lokasi, timezone per tugas, tanggal absensi wajib untuk tugas multi-hari, perubahan terbatas, pembatalan, notifikasi, dan activity log.

**Versi sederhana**

Atasan mengisi:

- satu atau lebih Pegawai;
- nama dan alamat lokasi;
- latitude, longitude, dan radius;
- tanggal mulai dan selesai.

Map picker tetap dipakai untuk mengisi alamat dan koordinat, dengan input manual sebagai fallback. Ini tidak membutuhkan master lokasi.

Sistem menyimpan satu tugas aktif per Pegawai. Pilihan banyak Pegawai hanya mempercepat satu kali input. HR hanya memonitor. Pegawai melihat tugasnya dan menekan **Lakukan Absensi**.

Model minimal:

```text
duty_trips
- id
- employee_id
- manager_id
- location_name
- address
- latitude
- longitude
- radius_meters
- starts_at
- ends_at
- status: active|cancelled
```

**Dihapus**

- master `DutyLocation` dan seluruh resource-nya;
- `duty_location_id`;
- timezone per tugas;
- `required_dates`;
- `approved_at`;
- edit jadwal kompleks setelah tugas berjalan;
- notification class `TripAssigned`.

Jika lokasi sering dipakai ulang dan input lokasi terbukti membebani Atasan, master lokasi dapat ditambahkan kembali.

### 5. Absensi

**Sekarang**

Absensi menilai jadwal harian, waktu perangkat versus server, akurasi GPS, jarak, keterlambatan 24 jam, foto privat, watermark, dan review HR.

**Versi sederhana**

Pegawai mengirim:

- koordinat browser;
- akurasi GPS;
- foto kamera.

Server menentukan:

- waktu penerimaan;
- jarak dari lokasi tugas;
- `valid` bila di dalam radius dan akurasi layak;
- `needs_review` bila di luar radius atau akurasi buruk.

Satu tugas hanya dapat memiliki satu absensi per hari. Tanggal ditentukan server dan constraint unik database menjadi pengaman utama.

Model minimal:

```text
attendances
- id
- duty_trip_id
- employee_id
- attendance_date
- received_at
- latitude
- longitude
- accuracy_meters
- distance_meters
- photo_path
- status: valid|needs_review
- review_reason
```

**Tetap wajib**

- pemeriksaan kepemilikan tugas di server;
- perhitungan jarak di server;
- foto disimpan privat;
- batas ukuran dan tipe foto;
- transaksi dan unique constraint untuk mencegah duplikasi;
- HR hanya boleh memutuskan data `needs_review`.

**Dihapus**

- `captured_at` dari perangkat;
- pemeriksaan perbedaan jam perangkat;
- status `late`;
- pilihan tanggal;
- toleransi keterlambatan 24 jam;
- notifikasi khusus absensi.

Tanggal wajib diturunkan langsung dari rentang mulai–selesai. `required_dates` baru diperlukan bila hari tertentu, seperti akhir pekan, harus dikecualikan.

### 6. KPI

**Sekarang**

HR membuat periode dan indikator berbobot. Atasan membuat KPI per Pegawai yang menunjuk indikator tersebut. Perubahan dikunci setelah merit dipublikasikan.

**Versi sederhana**

- HR membuat periode;
- Atasan menambah KPI langsung untuk bawahannya;
- KPI menyimpan `name`, `target`, `achievement`, dan `notes`;
- semua KPI dalam satu periode memiliki bobot sama;
- Pegawai hanya melihat KPI sendiri.

Model minimal:

```text
review_periods
- id
- name
- starts_at
- ends_at
- published_at

employee_kpis
- id
- review_period_id
- employee_id
- manager_id
- name
- target
- achievement
- notes
```

**Dihapus**

- `KpiIndicator` dan resource-nya;
- bobot per indikator;
- empat bobot yang dapat dikonfigurasi pada periode;
- reminder KPI terjadwal.

### 7. Penilaian dan Merit

**Sekarang**

Merit menggabungkan KPI, disiplin, penilaian Atasan, dan review 360. Hasil melewati verifikasi Atasan, verifikasi HR, lalu publikasi. Perhitungan dapat berjalan dari command atau tombol UI.

**Versi sederhana**

Rumus tetap:

```text
kpi_score        = rata-rata min(achievement / target, 120%) × 100
attendance_score = absensi harian valid / hari dinas wajib × 100
merit_score      = 80% kpi_score + 20% attendance_score
```

Alur:

1. HR memilih periode;
2. HR menekan **Hitung dan Publikasikan**;
3. sistem membuat satu hasil per Pegawai;
4. hasil langsung terkunci dan terlihat oleh Pegawai.

`MeritCalculator` tetap layak dipertahankan karena dipakai sebagai satu tempat untuk rumus dan transaksi. Namun hanya satu pintu pemanggil yang diperlukan.

**Dihapus**

- `PerformanceReview`;
- tipe review Atasan, bawahan, dan rekan;
- komponen `manager_score` dan `review_360_score`;
- estimasi bonus;
- verifikasi Atasan;
- verifikasi HR terpisah;
- `CalculateMerit` scheduler/command;
- `RemindKpi`;
- notification merit.

Tambahkan kembali verifikasi dua tahap hanya bila ada kebutuhan audit organisasi nyata.

### 8. Kompetensi dan Target Karier

**Sekarang**

Terdapat kamus kompetensi, standar kompetensi jabatan, level kompetensi Pegawai, target jabatan, kalkulasi gap, dan rekomendasi pelatihan/mentoring.

**Versi sederhana**

Ganti seluruh mesin gap dengan satu rencana pengembangan yang ditulis bersama:

```text
development_plans
- id
- employee_id
- target
- current_gap
- recommended_action
- review_date
```

Pegawai dan Atasan dapat melihat. Atasan atau HR dapat memperbarui.

**Dihapus**

- `Competency`;
- `PositionCompetency`;
- `EmployeeCompetency`;
- `CareerGoal`;
- `CareerGapService`;
- aturan level 1–5;
- rekomendasi pelatihan otomatis.

Kamus kompetensi layak kembali hanya bila organisasi sudah memiliki standar kompetensi resmi dan benar-benar akan memeliharanya.

### 9. Pelatihan dan Mentoring

**Sekarang**

Pelatihan memiliki katalog dan approval Atasan → HR → selesai. Mentoring memiliki workflow sendiri. Keduanya memakai enum, resource, aksi, notification, audit, row lock, resubmit, rekomendasi dari merit, dan sebagian dukungan delegasi.

**Versi sederhana**

Gabungkan menjadi satu modul:

```text
development_requests
- id
- employee_id
- manager_id
- type: training|mentoring
- title
- reason
- scheduled_at
- status: pending|approved|rejected|completed
- manager_notes
```

Alur:

1. Pegawai membuat pengajuan;
2. Atasan menyetujui atau menolak;
3. Atasan/HR menandai selesai.

HR dapat memonitor semua pengajuan, tetapi tidak menjadi approval kedua.

**Tetap wajib**

- Pegawai hanya mengajukan untuk dirinya;
- Atasan hanya memutus pengajuan bawahan langsung;
- perpindahan status divalidasi;
- perubahan status dilakukan dalam transaksi.

**Dihapus**

- katalog `Training`;
- `TrainingRequest` dan `Mentoring` terpisah;
- `HasWorkflow`;
- resubmit khusus;
- rekomendasi pelatihan dari merit;
- verifikasi HR;
- `ApprovalChain`;
- delegasi;
- eskalasi tiga hari;
- seluruh notification class pelatihan/mentoring.

### 10. Laporan

**Sekarang**

HR memperoleh filter, pilihan kolom, grouping, CSV, PDF, XLSX, halaman HTML, dan email laporan terjadwal.

**Versi sederhana**

- satu tabel Filament;
- filter periode, unit, dan jabatan;
- kolom tetap: Pegawai, unit, jabatan, absensi, KPI, merit, dan pengembangan;
- satu ekspor CSV menggunakan response bawaan PHP/Laravel.

**Dihapus**

- PDF;
- XLSX;
- pemilihan kolom;
- grouping dinamis;
- halaman Blade laporan terpisah;
- `ReportMail`;
- `SendReport`;
- email laporan bulanan;
- dependency `barryvdh/laravel-dompdf`;
- dependency `openspout/openspout`.

PDF/XLSX dapat ditambahkan kembali saat penerima laporan menyatakan CSV tidak cukup.

### 11. Audit, Notifikasi, dan Scheduler

**Versi sederhana**

`ActivityLog` dipertahankan hanya untuk:

- review absensi;
- publikasi merit;
- persetujuan/penolakan pengembangan.

Daftar tertunda pada menu menggantikan database notification. Flash notification Filament cukup untuk hasil aksi pengguna.

Scheduler aplikasi tidak diperlukan. Backup database menjadi tanggung jawab hosting/database, bukan fitur web. Untuk server mandiri, jalankan `mysqldump` dari cron di luar Laravel.

**Dihapus**

- `ApprovalChain`;
- `EscalateApprovals`;
- delapan notification class;
- polling notification;
- `BackupDatabase`;
- `SendReport`;
- `RemindKpi`;
- `CalculateMerit`.

## Struktur Data Akhir

Sebelas tabel domain cukup:

1. `users`;
2. `units`;
3. `positions`;
4. `duty_trips`;
5. `attendances`;
6. `review_periods`;
7. `employee_kpis`;
8. `merit_results`;
9. `development_plans`;
10. `development_requests`;
11. `activity_logs`.

Tabel framework seperti sessions, cache, jobs, dan notifications hanya dipertahankan bila fitur framework tersebut benar-benar dipakai.

## Alur Pengguna Akhir

### Pegawai

1. login ke `/app`;
2. melihat tugas aktif;
3. mengirim satu absensi berfoto setiap hari selama dinas;
4. melihat KPI dan merit;
5. melihat rencana pengembangan;
6. mengajukan pelatihan atau mentoring.

### Atasan

1. membuat tugas untuk satu atau lebih bawahan;
2. mengisi KPI bawahan;
3. menyetujui pengajuan pengembangan;
4. melihat ringkasan tim.

### HR

1. mengelola pengguna, unit, dan jabatan;
2. memeriksa absensi bermasalah;
3. membuat periode dan mempublikasikan merit;
4. memonitor rencana/pengajuan pengembangan;
5. melihat dan mengekspor laporan CSV.

## Temuan Ponytail, Urutan Dampak Terbesar

1. `shrink:` tiga panel dan 15 widget menjadi satu panel dan satu widget statistik. Resource tetap dilindungi policy/query scope.
2. `delete:` `ApprovalChain`, delegasi, dan eskalasi hanya menambah penerima notifikasi; status bisnis tidak berubah. Ganti dengan satu approval Atasan.
3. `shrink:` kompetensi, karier, pelatihan, dan mentoring menjadi `development_plans` + `development_requests`.
4. `delete:` penilaian 360 dan verifikasi merit bertingkat. Ganti rumus tetap dan satu aksi HR.
5. `delete:` master indikator KPI. Simpan nama/target/capaian langsung pada KPI Pegawai.
6. `native:` PDF dan XLSX diganti CSV bawaan. Lepas dua dependency runtime.
7. `shrink:` perjalanan dinas multi-hari memakai tanggal server dan satu absensi per hari, tanpa timezone atau daftar tanggal khusus.
8. `delete:` table widget dashboard. Daftar resource terfilter sudah menyediakan data yang sama.
9. `delete:` delapan notification class dan polling. Gunakan badge jumlah tertunda dan flash notification.
10. `native:` backup database dipindahkan ke fasilitas hosting/cron database.

## Yang Sengaja Tidak Disederhanakan

- autentikasi dan regenerasi session;
- pembatasan data berdasarkan role dan hubungan Atasan–Pegawai;
- validasi input pada server;
- penyimpanan foto secara privat;
- geofence yang dihitung pada server;
- unique constraint absensi;
- transaksi untuk pencatatan absensi, publikasi merit, dan perubahan workflow;
- audit untuk keputusan penting.

## Dampak Perkiraan

Perkiraan setelah implementasi:

- 20 domain Filament menjadi sekitar 10 domain;
- 91 file resource menjadi sekitar 35–45 file;
- 15 widget menjadi satu widget;
- 21 model menjadi 11 model;
- 8 notification class menjadi nol;
- 5 command menjadi nol;
- 9.318 baris PHP aplikasi menjadi sekitar 3.500–4.000 baris;
- dua dependency runtime dapat dilepas.

Angka ini estimasi desain, bukan diff aktual. Implementasi harus dilakukan bertahap dengan test alur inti sebelum menghapus tabel atau fitur lama.

`net: -5.500 lines, -2 deps possible.`
