# Audit Laporan Proyek Akhir Semester ABL

- Sumber: `Proyek Akhir Semester ABL.pdf`
- Tanggal audit: 9 Agustus 2026
- Cakupan: kesesuaian klaim laporan dengan kode, migration, konfigurasi, dan automated test aktif
- Acuan halaman: nomor halaman PDF; nomor cetak di dalam laporan ditulis dalam kurung

## Kesimpulan

Laporan **belum sepenuhnya sesuai** dengan implementasi saat ini. Modul inti—tiga panel peran, Google Maps, dinas, GPS, kamera, watermark, merit, pembinaan karier, laporan, dan backup—memang tersedia. Namun laporan masih mengklaim beberapa komponen yang sudah tidak ada atau tidak pernah menjadi bagian kode aktif:

- SOA dengan layanan independen dan RESTful API sebagai komunikasi utama;
- Laravel Sanctum, Socialite, dan Reverb;
- verifikasi wajah dengan `face-api.js`;
- antrean absensi luring dengan IndexedDB dan service worker;
- Web Push, WhatsApp, dan preferensi kanal notifikasi;
- retensi foto terjadwal;
- status `OutsideRadius` dan `PendingSync`;
- penilaian 360 sebagai satu komponen merit penuh;
- beberapa diagram, cuplikan kode, screenshot, dan hasil black-box yang tidak menggambarkan kode aktif.

Perbaikan paling penting: ubah klaim arsitektur menjadi **modular monolith dengan application service layer**, hapus fitur yang tidak ada, perbarui diagram, dan ganti matriks pengujian yang masih menguji fitur luring.

## Bagian yang Sudah Sesuai

- Laravel 12, Filament 5, Tailwind CSS, Alpine.js, dan MySQL digunakan oleh aplikasi. Requirement PHP adalah `^8.2`; runtime saat audit adalah PHP 8.3.6.
- Tiga panel tersedia: Pegawai (`/pegawai`), Atasan (`/atasan`), dan HR (`/hr`).
- Google Maps digunakan untuk pencarian serta pemilihan lokasi dinas.
- Absensi memakai Browser Geolocation API, kamera langsung, watermark, dan rumus Haversine.
- Merit memakai KPI, kepatuhan dinas, penilaian Atasan, umpan balik rekan, bobot per periode, dan publikasi dua tahap.
- Analisis gap kompetensi, pelatihan, rekomendasi oleh Atasan, mentoring, laporan HR, ekspor, audit aktivitas, scheduler, dan backup tersedia.
- Automated test aktif lulus: **89 test, 541 assertion**.

## Temuan dan Teks Perbaikan

### 1. Arsitektur diklaim SOA dan RESTful, padahal implementasinya modular monolith

**Prioritas:** kritis

**Lokasi laporan:**

- PDF hlm. 13–15 (cetak 7–9), bagian 2.1;
- PDF hlm. 24–25 (cetak 18–19), bagian 3.2.1–3.2.2;
- PDF hlm. 32–33 (cetak 26–27), bagian 4.2;
- PDF hlm. 60 (cetak 54), bagian 6.2 poin 1;
- PDF hlm. 62 (cetak 56), bagian 7.1 poin 2.

**Masalah:** aplikasi berjalan sebagai satu Laravel application, satu proses, dan satu database. Filament, Livewire, dan Blade memakai autentikasi session melalui `routes/web.php`. Tidak ada `routes/api.php`, kontrak layanan independen, atau layanan yang dapat di-deploy terpisah. `AttendanceRecorder`, `MeritCalculator`, dan `CareerGapService` adalah class pada application service layer, bukan network service.

**Teks pengganti:**

> Sistem ABL diimplementasikan sebagai modular monolith berbasis Laravel 12. Antarmuka utama dibangun dengan Filament, Livewire, dan Blade menggunakan autentikasi session. Logika bisnis dipisahkan ke dalam application service class, terutama `AttendanceRecorder`, `MeritCalculator`, dan `CareerGapService`, sehingga tanggung jawab modul tetap terstruktur walaupun seluruh komponen berjalan dalam satu aplikasi dan menggunakan satu basis data. Endpoint penyimpanan absensi mengembalikan JSON untuk kebutuhan halaman browser, tetapi tetap berada pada web middleware dengan session dan proteksi CSRF; endpoint tersebut bukan REST API independen.

**Catatan:** jika istilah “arsitektur berbasis layanan” wajib karena konteks mata kuliah, gunakan istilah **service layer pada modular monolith**, bukan SOA terdistribusi, stateless REST, atau layanan independen.

### 2. Sanctum, Socialite, dan Reverb tidak digunakan

**Prioritas:** tinggi

**Lokasi laporan:**

- PDF hlm. 20–21 (cetak 14–15), bagian 2.3.9–2.3.11;
- PDF hlm. 14–15, bagian integrasi antarlayanan;
- PDF hlm. 28, teori WebSocket;
- PDF hlm. 63 (cetak 57), saran poin 2.

**Masalah:** ketiga package tidak tercantum pada dependency aktif. Login menggunakan email dan kata sandi dengan Laravel session. Tidak ada OAuth Google, token Sanctum, atau pembaruan real-time melalui Reverb.

**Teks pengganti:**

> Autentikasi ABL menggunakan session bawaan Laravel. Pengguna masuk melalui satu halaman login menggunakan email dan kata sandi, kemudian diarahkan ke panel sesuai peran. Percobaan login dibatasi dengan rate limiting. Implementasi saat ini tidak menggunakan Laravel Sanctum, Laravel Socialite, OAuth Google, atau Laravel Reverb.

Untuk saran pengembangan, ganti frasa “memanfaatkan Laravel Reverb secara lebih ekstensif” menjadi:

> Jika kebutuhan notifikasi real-time telah terukur, Laravel Reverb dapat ditambahkan sebagai komponen baru. Implementasi saat ini belum memakai WebSocket.

### 3. Verifikasi wajah tidak ada

**Prioritas:** kritis

**Lokasi laporan:**

- PDF hlm. 8–12, latar belakang, tujuan, dan batasan;
- PDF hlm. 15 dan 20, `face-api.js`;
- PDF hlm. 24, Tabel 1;
- PDF hlm. 27, bagian 3.2.7;
- PDF hlm. 33, integration layer;
- PDF hlm. 60 dan 62, pembahasan serta kesimpulan.

**Masalah:** tidak ada `face-api.js`, model wajah, face descriptor, `FaceVerificationController`, atau verifikasi biometrik. Foto hanya menjadi bukti visual privat.

**Teks pengganti:**

> Absensi dinas menggunakan foto kamera langsung sebagai bukti visual, tanpa pengenalan atau pencocokan biometrik wajah. Browser mengambil foto, koordinat GPS, akurasi lokasi, dan waktu perangkat. Foto diberi watermark nama Pegawai, waktu, koordinat, dan lokasi dinas sebelum dikirim ke server. Akses foto dibatasi berdasarkan peran dan relasi pengguna terhadap tugas dinas.

Untuk Tabel 1, ganti klaim “deteksi/verifikasi wajah” menjadi:

> ABL menambahkan validasi radius dengan rumus Haversine, pemeriksaan akurasi GPS, foto kamera langsung dengan watermark, dan alur pemeriksaan manual oleh HR untuk data yang meragukan.

### 4. Penyimpanan dan sinkronisasi absensi luring tidak ada

**Prioritas:** kritis

**Lokasi laporan:**

- PDF hlm. 14 dan 21, klaim endpoint sinkronisasi luring;
- PDF hlm. 24, Tabel 1;
- PDF hlm. 30, FR-ABS-11 dan FR-ABS-12;
- PDF hlm. 32, NFR-06;
- PDF hlm. 36, activity diagram langkah 8;
- PDF hlm. 58, test C-09 dan C-10;
- PDF hlm. 61, pembahasan poin 6.

**Masalah:** halaman absensi hanya menampilkan status jaringan. Saat `navigator.onLine` bernilai false, submit ditolak. Tidak ada IndexedDB, service worker, antrean lokal, atau background sync.

**Teks pengganti:**

> Halaman absensi menampilkan status koneksi perangkat. Pengambilan dan pengiriman absensi memerlukan koneksi ke server. Jika perangkat sedang luring, sistem menolak pengiriman dan meminta Pegawai menyambungkan internet sebelum mencoba kembali. Implementasi saat ini tidak menyimpan antrean absensi pada IndexedDB dan tidak melakukan sinkronisasi otomatis.

Perubahan requirement:

- Ganti FR-ABS-11 dengan: “Sistem harus memberi pesan yang jelas ketika absensi tidak dapat dikirim karena perangkat luring.”
- Hapus FR-ABS-12.
- Ganti NFR-06 dengan: “Halaman absensi harus menampilkan status jaringan dan mencegah submit ketika perangkat luring.”
- Hapus test C-09 dan C-10, atau tandai **Tidak Diimplementasikan**.

### 5. Klaim notifikasi multisaluran terlalu luas

**Prioritas:** tinggi

**Lokasi laporan:**

- PDF hlm. 15, integrasi antarlayanan;
- PDF hlm. 33, integration dan notification layer;
- PDF hlm. 53 (cetak 47), bagian 5.2.7;
- PDF hlm. 62, kesimpulan poin 2.

**Masalah:** trait `HasDynamicChannels`, field `notification_preferences`, `WebpushChannel`, dan `WhatsAppChannel` tidak ada. Notifikasi aktif memakai channel database; `TripAssigned`, `MeritPublished`, dan `AttendanceNeedsReview` juga memakai email. Hanya kebutuhan tertentu yang memakai queue.

**Teks pengganti:**

> Sistem menggunakan notifikasi database untuk pemberitahuan di dalam aplikasi. Notifikasi penugasan dinas, publikasi merit, dan absensi yang memerlukan pemeriksaan juga dapat dikirim melalui email. Laporan periodik HR dikirim melalui perintah scheduler. Implementasi saat ini tidak menyediakan preferensi kanal per pengguna, Web Push, WhatsApp, atau notifikasi WebSocket.

### 6. Retensi foto terjadwal tidak ada

**Prioritas:** sedang

**Lokasi laporan:**

- PDF hlm. 17–18, fase konstruksi dan scheduler;
- PDF hlm. 25, task scheduling.

**Masalah:** scheduler aktif menjalankan backup, kalkulasi merit, pengingat KPI, laporan merit, dan eskalasi persetujuan. Tidak ada command penghapusan atau retensi foto otomatis.

**Teks pengganti:**

> Laravel Scheduler menjalankan backup database setiap hari, eskalasi persetujuan, pengingat KPI, kalkulasi merit bulanan, dan pengiriman laporan periodik. Foto absensi disimpan pada private local storage dan hanya dapat diakses melalui endpoint berotorisasi. Kebijakan serta penghapusan foto otomatis belum diimplementasikan.

### 7. Istilah dan formula merit perlu diperjelas

**Prioritas:** tinggi

**Lokasi laporan:**

- PDF hlm. 10, tujuan proyek;
- PDF hlm. 30, FR-MRT-05–FR-MRT-07;
- PDF hlm. 38, activity diagram merit;
- PDF hlm. 52, Tabel 6;
- PDF hlm. 58, test C-13–C-17;
- PDF hlm. 60 dan 62, pembahasan serta kesimpulan.

**Masalah:** implementasi menyimpan tipe Atasan→Pegawai, Pegawai→Atasan, dan Rekan→Pegawai, tetapi kalkulasi merit hanya memakai Atasan→Pegawai untuk skor Atasan dan `Peer` untuk umpan balik rekan. Penilaian Pegawai→Atasan tidak menjadi skor peer. Bobot 40/20/20/20 adalah contoh data, bukan nilai tetap. Istilah UI adalah “kepatuhan dinas”, “umpan balik rekan”, dan “simulasi bonus”.

**Teks pengganti:**

> Sistem merit menggabungkan skor KPI, kepatuhan dinas, penilaian Atasan, dan umpan balik rekan. HR menentukan bobot setiap komponen pada periode penilaian dan total bobot wajib 100%. Skor kepatuhan dinas dihitung dari tanggal dinas selesai yang memiliki absensi berstatus Valid. Skor Atasan memakai penilaian Atasan kepada Pegawai, sedangkan skor umpan balik rekan hanya memakai penilaian `Peer`. Penilaian Pegawai kepada Atasan tetap tersimpan sebagai umpan balik, tetapi tidak dihitung sebagai peer feedback. Hasil menampilkan simulasi bonus dan tidak terhubung ke payroll. Hasil baru dapat dipublikasikan HR setelah diverifikasi Atasan dan periode berakhir.

Ganti baris `360` pada Tabel 6 menjadi:

> Umpan balik rekan — `avg(performance_reviews.type=peer) / 5 × 100`, rentang 0–100, bobot dapat dikonfigurasi per periode.

### 8. Status DutyTrip dan Attendance pada diagram tidak sesuai

**Prioritas:** tinggi

**Lokasi laporan:** PDF hlm. 43–44 (cetak 37–38), bagian 4.3.4.

**Masalah:** laporan mencantumkan status DutyTrip `Pending`, `Rejected`, dan `Completed`, serta status Attendance `OutsideRadius` dan `PendingSync`. Enum aktif hanya berisi:

- DutyTrip: `Approved` dan `Cancelled`;
- Attendance: `Valid`, `Late`, dan `NeedsReview`.

Data di luar radius, akurasi GPS kosong/melewati batas, atau jam perangkat menyimpang menjadi `NeedsReview`. HR dapat memverifikasi `NeedsReview` menjadi `Valid`.

**Teks pengganti:**

> Perintah dinas langsung berstatus `Approved` atau “Ditugaskan” setelah dibuat dan dapat berubah menjadi `Cancelled` atau “Dibatalkan” sesuai aturan bisnis. Absensi memiliki status `Valid`, `Late`, dan `NeedsReview`. Lokasi di luar radius tidak menghasilkan status khusus `OutsideRadius`; data tersebut disimpan sebagai `NeedsReview` beserta alasan pemeriksaan.

### 9. Class Diagram dan ERD memakai model lama

**Prioritas:** kritis

**Lokasi laporan:**

- PDF hlm. 43, Gambar 10 Class Diagram;
- PDF hlm. 45 (cetak 39), Gambar 11 ERD.

**Masalah:** diagram memakai `roles`, `employees`, `departments`, `business_trips`, dan `email_notifications`. Tabel tersebut tidak ada. Semua pengguna disimpan pada `users` dengan enum role; organisasi memakai `units` dan `positions`; dinas memakai `duty_locations`, `duty_trips`, dan `attendances`; notifikasi memakai tabel `notifications`. Diagram juga tidak memuat banyak tabel aktif.

**Tindakan:** Gambar 10 dan Gambar 11 harus dibuat ulang dari migration aktif. Jangan hanya mengganti caption.

**Teks pendamping pengganti:**

> Model data ABL berpusat pada tabel `users`, yang menyimpan seluruh peran dan relasi Atasan–Pegawai melalui `manager_id`. Struktur organisasi menggunakan `units` dan `positions`. Modul dinas menggunakan `duty_locations`, `duty_trips`, dan `attendances`. Modul merit menggunakan `review_periods`, `kpi_indicators`, `employee_kpis`, `performance_reviews`, dan `merit_results`. Modul pembinaan karier menggunakan `competencies`, `position_competency`, `employee_competencies`, `career_goals`, `trainings`, `training_requests`, dan `mentorings`. Audit serta operasional menggunakan `activity_logs`, `notifications`, `approval_chains`, dan tabel queue/cache Laravel.

### 10. Cuplikan kode pada Bab 5 tidak sama dengan source aktif

**Prioritas:** tinggi

#### 10.1 MapPicker — PDF hlm. 48 (cetak 42)

Method `setUp()` dan default koordinat pada cuplikan laporan tidak ada. Class aktif hanya menetapkan view.

**Teks/kode pengganti:**

```php
class MapPicker extends Field
{
    protected string $view = 'filament.forms.components.map-picker';
}
```

> Inisialisasi Google Maps, marker, pencarian Places, geocoding, dan sinkronisasi latitude/longitude diterapkan pada `resources/views/filament/forms/components/map-picker.blade.php`.

#### 10.2 Absensi GPS dan kamera — PDF hlm. 49 (cetak 43)

File `attendance-capture.js`, pemanggilan Axios, dan endpoint `/attendance/{id}/store` tidak ada.

**Teks pengganti:**

> Logika kamera dan GPS berada langsung pada `resources/views/attendance/capture.blade.php`. Browser memakai `getUserMedia()` untuk kamera, `getCurrentPosition()` untuk GPS, Canvas untuk watermark, dan `fetch()` untuk mengirim `FormData` ke route `POST /pegawai/dinas/{dutyTrip}/absensi` dengan session, CSRF token, serta respons JSON.

#### 10.3 GeoDistance — PDF hlm. 50 (cetak 44)

Method aktif bernama `meters()`, memakai radius bumi `6.371.000` meter, dan mengembalikan integer meter; bukan `haversine()` dalam kilometer.

**Teks pengganti:**

> `GeoDistance::meters()` menghitung jarak Haversine antara koordinat dinas dan koordinat Pegawai, lalu membulatkan hasil ke meter. `AttendanceRecorder` membandingkan nilai ini dengan `dutyTrip.radius_meters`.

#### 10.4 Watermark — PDF hlm. 51 (cetak 45)

Watermark tidak dibuat server-side oleh `AttendanceRecorder` dan tidak memakai `Image::make()`.

**Teks pengganti:**

> Watermark dibuat di browser melalui Canvas sebelum foto dikirim. Watermark memuat nama Pegawai, waktu pengambilan, koordinat GPS, dan nama lokasi dinas. Server memvalidasi file gambar maksimal 5 MB lalu menyimpannya pada private local storage.

#### 10.5 MeritCalculator — PDF hlm. 51–52 (cetak 45–46)

Signature dan alur cuplikan salah. Service aktif menghitung satu Pegawai melalui `calculate(ReviewPeriod $period, User $employee): MeritResult`; command dan aksi Filament menangani pemilihan Pegawai.

**Teks pengganti:**

> `MeritCalculator` menghitung satu hasil merit per Pegawai dan periode di dalam transaksi database. Service memvalidasi kelengkapan KPI, penilaian Atasan, dan umpan balik rekan sesuai bobot aktif; menghitung skor; lalu membuat atau memperbarui `MeritResult` selama hasil belum diverifikasi. Hasil yang sudah diverifikasi atau dipublikasikan tidak dapat dihitung ulang.

#### 10.6 CareerGapService — PDF hlm. 52–53 (cetak 46–47)

Service aktif menerima `CareerGoal`, mengembalikan `Collection`, memakai `required_level`, dan menyertakan rekomendasi pelatihan aktif atau mentoring.

**Teks pengganti:**

> `CareerGapService::analyze(CareerGoal $goal)` mengambil standar kompetensi jabatan tujuan, membandingkannya dengan level kompetensi terkini Pegawai, menghitung gap, lalu menampilkan nama pelatihan aktif yang relevan. Jika tidak ada pelatihan aktif, rekomendasi yang ditampilkan adalah “Ajukan mentoring”.

#### 10.7 Notifikasi — PDF hlm. 53 (cetak 47)

Hapus seluruh cuplikan `HasDynamicChannels`; trait dan kanal tersebut tidak ada. Gunakan teks pengganti pada temuan nomor 5.

### 11. Matriks black-box memuat hasil yang mustahil pada kode aktif

**Prioritas:** kritis

**Lokasi laporan:** PDF hlm. 57–59 (cetak 51–53), Tabel 7.

**Koreksi wajib:**

| Test | Masalah | Perbaikan |
| --- | --- | --- |
| C-02 | Hasil memakai pesan Inggris | Ganti hasil dengan `Email atau kata sandi tidak valid.` |
| C-07 | Mengharapkan `OutsideRadius` | Ganti menjadi `NeedsReview` / `Memerlukan Pemeriksaan` dengan alasan lokasi di luar radius |
| C-09 | Mengklaim IndexedDB lulus | Hapus atau tandai `Tidak Diimplementasikan` |
| C-10 | Mengklaim sinkronisasi luring lulus | Hapus atau tandai `Tidak Diimplementasikan` |
| C-13/C-14 | Disebut “penilaian 360” penuh | Ubah menjadi “Umpan Balik Kinerja”; jelaskan bahwa upward feedback tidak menjadi peer score |
| C-15 | Komponen `360` memasukkan upward review | Ganti komponen terakhir menjadi umpan balik `Peer` |
| C-17 | Tidak menyebut batas tanggal publikasi | Tambahkan bahwa HR hanya dapat mempublikasikan setelah periode selesai |
| C-28 | Klaim cron berhasil tanpa bukti eksekusi yang dicantumkan | Cantumkan log/file backup pengujian atau batasi klaim pada automated test backup SQLite |

**Teks pengganti untuk hasil pengujian:**

> Automated test dijalankan pada 9 Agustus 2026 dan menghasilkan 89 test dengan 541 assertion lulus. Cakupan meliputi autentikasi dan hak akses, dinas dan absensi, merit, pembinaan karier, laporan, ekspor, serta backup SQLite. Skenario browser dan perangkat fisik tetap merupakan pengujian manual; status “Sesuai” hanya boleh diberikan setelah skenario tersebut dijalankan ulang pada build laporan. Fitur antrean serta sinkronisasi absensi luring tidak termasuk implementasi aktif.

Hapus kalimat “28 skenario menunjukkan seluruh fungsi siap digunakan dalam lingkungan operasional” karena C-09 dan C-10 tidak mungkin lulus pada kode aktif.

### 12. Screenshot memakai UI dan alur lama

**Prioritas:** tinggi

**Lokasi laporan:** PDF hlm. 54–56 (cetak 48–50), Gambar 20–23.

**Masalah:** screenshot masih menampilkan label `Riwayat Absensi`/`Monitoring Absensi`, sedangkan UI aktif memakai `Riwayat Absensi Dinas`/`Monitoring Absensi Dinas`. Gambar 21 masih menampilkan input file dan klaim penyimpanan luring, sedangkan halaman aktif memakai tombol buka kamera, ambil foto, ambil lokasi, lalu submit online.

**Tindakan:** ambil ulang screenshot dashboard Pegawai, halaman absensi, dashboard Atasan, dan dashboard HR dari build aktif.

**Teks pengganti untuk halaman absensi:**

> Halaman absensi dinas menyediakan pengambilan foto langsung dari kamera dan pembacaan GPS berakurasi tinggi. Setelah foto diambil, sistem membaca lokasi, membuat watermark, dan mengirim data melalui koneksi online. Jika perangkat luring, halaman menampilkan pesan agar pengguna menyambungkan internet sebelum mencoba kembali.

### 13. Kesimpulan Bab 7 perlu diganti

**Prioritas:** kritis

**Lokasi laporan:** PDF hlm. 62–63 (cetak 56–57), bagian 7.1–7.2.

**Teks pengganti yang disarankan:**

> 1. Sistem ABL berhasil dibangun sebagai aplikasi web modular monolith menggunakan Laravel 12, PHP 8.2 atau lebih baru, MySQL, dan Filament 5 dengan tiga panel terintegrasi untuk Pegawai, Atasan, dan Admin SDM/HR.
> 2. Pemisahan logika bisnis melalui `AttendanceRecorder`, `MeritCalculator`, dan `CareerGapService` membuat modul lebih terstruktur dan mudah diuji, walaupun seluruh layanan masih berjalan dalam satu aplikasi dan satu basis data.
> 3. Absensi dinas berhasil menerapkan GPS, validasi radius Haversine, pemeriksaan akurasi serta waktu perangkat, kamera langsung, dan watermark foto. Implementasi saat ini tidak mencakup verifikasi wajah atau sinkronisasi luring.
> 4. Merit menggabungkan KPI, kepatuhan dinas, penilaian Atasan, dan umpan balik rekan dengan bobot yang dapat dikonfigurasi dan wajib berjumlah 100%. Hasil melewati verifikasi Atasan dan publikasi HR setelah periode berakhir. Nilai bonus bersifat simulasi dan tidak terhubung ke payroll.
> 5. Pembinaan karier mencakup analisis gap kompetensi, rekomendasi pelatihan atau mentoring, alur persetujuan pelatihan, rekomendasi langsung oleh Atasan berdasarkan merit terpublikasi, serta pencatatan mentoring.
> 6. Automated test pada build yang diaudit menghasilkan 89 test dan 541 assertion lulus. Pengujian perangkat fisik, browser, scheduler production, dan restore backup tetap harus dilaporkan terpisah sebagai pengujian manual.

Untuk bagian saran:

- tulis Reverb dan offline sync sebagai fitur **baru** jika nanti dibutuhkan, bukan perluasan fitur yang sudah ada;
- hapus Payment Gateway kecuali scope produk memang diubah dari simulasi bonus menjadi payroll/payment;
- pertahankan multi-tenancy dan 2FA hanya sebagai opsi masa depan, bukan kekurangan terhadap scope saat ini.

## Acuan Implementasi yang Diverifikasi

- `composer.json`
- `routes/web.php`
- `app/Http/Controllers/AuthenticatedSessionController.php`
- `app/Http/Controllers/AttendanceController.php`
- `resources/views/attendance/capture.blade.php`
- `app/Services/AttendanceRecorder.php`
- `app/Support/GeoDistance.php`
- `app/Services/MeritCalculator.php`
- `app/Services/CareerGapService.php`
- `app/Enums/DutyTripStatus.php`
- `app/Enums/AttendanceStatus.php`
- `app/Notifications/`
- `routes/console.php`
- `database/migrations/`
- `tests/Unit/` dan `tests/Feature/`

## Urutan Revisi Paling Hemat

1. Ganti bagian 7.1 Kesimpulan dan 6.2 Pembahasan.
2. Hapus semua klaim face recognition, offline sync, Sanctum, Socialite, Reverb, Web Push, dan WhatsApp.
3. Perbarui Tabel 2, Tabel 3, Tabel 6, dan Tabel 7.
4. Ganti Gambar 10, Gambar 11, serta screenshot Gambar 20–23.
5. Ganti cuplikan kode Bab 5 dengan teks ringkas atau source aktif.
6. Terakhir, sinkronkan abstrak, latar belakang, tujuan, batasan, dan daftar isi.
