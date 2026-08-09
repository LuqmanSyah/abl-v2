# BAB VI

# HASIL DAN PEMBAHASAN

## 6.1 Hasil Pengujian Sistem

### 6.1.1 Lingkungan Pengujian

Pengujian otomatis dijalankan melalui `php artisan test`. Laravel memakai environment `testing` dan basis data SQLite in-memory sehingga setiap test terisolasi dari data lokal. Suite menggunakan PHPUnit 11 dan mencakup unit test, HTTP feature test, model serta service integration test, dan komponen Livewire/Filament.

Eksekusi pada **9 Agustus 2026** menghasilkan:

- **105 test lulus**;
- **603 assertion lulus**;
- **0 kegagalan**;
- durasi sekitar **6,42 detik** pada lingkungan pengembangan saat laporan diperbarui.

Hasil tersebut merupakan bukti pengujian otomatis, bukan klaim bahwa seluruh kombinasi browser, perangkat kamera, sensor GPS, mail server, queue worker production, atau beban besar telah diuji.

### 6.1.2 Inventaris Pengujian Otomatis

| File test | Jumlah test | Cakupan utama |
| --- | ---: | --- |
| `tests/Feature/DutyAttendanceTest.php` | 20 | Haversine, status, waktu, akurasi, idempotensi, foto, verifikasi HR, widget |
| `tests/Feature/DutyTripManagementTest.php` | 7 | Pembuatan, perubahan, pembatalan, ownership, dan visibilitas dinas |
| `tests/Feature/FilamentAccessTest.php` | 15 | Login, redirect, isolasi panel, resource, tombol aksi, dan hak akses |
| `tests/Feature/MeritSystemTest.php` | 21 | Formula, kelengkapan, bobot, review, audit, lock, verifikasi, dan publikasi |
| `tests/Feature/CareerDevelopmentTest.php` | 13 | Gap, target karier, kompetensi, rekomendasi, workflow, dan resource |
| `tests/Feature/TrainingWorkflowTest.php` | 10 | Pengajuan, persetujuan, penolakan, pengajuan ulang, verifikasi, penyelesaian |
| `tests/Feature/MentoringWorkflowTest.php` | 8 | Pengajuan, persetujuan, penolakan, jadwal, dan penyelesaian |
| `tests/Feature/OperationsReportTest.php` | 4 | Scope laporan, filter, ekspor aman, email report, dan foto privat |
| `tests/Feature/FlowTest.php` | 1 | Alur SDM lintas modul |
| `tests/Feature/DatabaseSeederTest.php` | 1 | Data master dan idempotensi seeder |
| `tests/Feature/ExampleTest.php` | 3 | Root page, login responsif, dan pesan validasi Indonesia |
| `tests/Unit/SqliteBackupTest.php` | 1 | Validitas dan pemulihan backup SQLite |
| `tests/Unit/ExampleTest.php` | 1 | Sanity test lingkungan PHPUnit |
| **Total** | **105** | **603 assertion** |

### 6.1.3 Matriks Skenario Representatif

| Kode | Skenario | Hasil yang diharapkan | Status |
| --- | --- | --- | --- |
| T-01 | Pengguna aktif login | Dialihkan ke panel sesuai peran | Lulus otomatis |
| T-02 | Pengguna tidak aktif login | Login ditolak | Lulus otomatis |
| T-03 | Pengguna membuka panel peran lain | Akses ditolak | Lulus otomatis |
| T-04 | Atasan menugaskan bawahan langsung | Dinas tersimpan dan terlihat oleh Pegawai | Lulus otomatis |
| T-05 | Atasan menugaskan Pegawai milik Atasan lain | Operasi ditolak | Lulus otomatis |
| T-06 | Pembatalan dinas setelah ada absensi | Operasi ditolak | Lulus otomatis |
| T-07 | Absensi di dalam radius, akurasi dan waktu wajar | Status `Valid` | Lulus otomatis |
| T-08 | GPS tidak akurat atau di luar radius | Status `NeedsReview` dan HR diberi notifikasi | Lulus otomatis |
| T-09 | Absensi sebelum jadwal dimulai | Operasi ditolak | Lulus otomatis |
| T-10 | Request absensi dikirim ulang | Record tidak berduplikasi dan foto baru dibersihkan | Lulus otomatis |
| T-11 | Pengguna tidak terkait menebak URL foto | Akses ditolak | Lulus otomatis |
| T-12 | Total bobot komponen merit bukan 100% | Periode ditolak | Lulus otomatis |
| T-13 | Data KPI/review wajib belum lengkap | Kalkulasi merit ditolak dengan alasan | Lulus otomatis |
| T-14 | Kalkulasi merit dengan data lengkap | Skor komponen, total, dan simulasi bonus sesuai formula | Lulus otomatis |
| T-15 | Penilaian Pegawai kepada Atasan | Tersimpan tetapi tidak dihitung sebagai peer feedback Pegawai | Lulus otomatis |
| T-16 | Publikasi tanpa verifikasi dua tahap atau sebelum periode selesai | Operasi ditolak | Lulus otomatis |
| T-17 | Pegawai memilih jabatan tujuan setara/lebih rendah | Target ditolak | Lulus otomatis |
| T-18 | Pengajuan pelatihan melewati seluruh workflow | Status berubah sesuai aktor dan urutan | Lulus otomatis |
| T-19 | Mentoring diselesaikan sebelum jadwal | Operasi ditolak | Lulus otomatis |
| T-20 | HR mengekspor nilai yang menyerupai formula spreadsheet | Nilai dinetralkan | Lulus otomatis |
| T-21 | Backup SQLite dibuat lalu dipulihkan | Berkas valid dan dapat dibuka | Lulus otomatis |

### 6.1.4 Pengujian Manual yang Tetap Diperlukan

Beberapa perilaku bergantung pada browser, perangkat, dan layanan eksternal sehingga harus diuji manual pada build yang akan dipresentasikan.

| Area | Langkah ringkas | Bukti yang diperlukan |
| --- | --- | --- |
| Kamera | Buka halaman absensi, izinkan kamera, ambil foto | Foto dan watermark tampil benar |
| GPS | Uji lokasi dalam radius, luar radius, dan akurasi buruk | Koordinat, akurasi, jarak, serta status sesuai |
| Google Maps | Cari lokasi, pindah marker, simpan dinas | Alamat, koordinat, dan radius tersimpan |
| Responsive | Buka login, panel, dan absensi pada ponsel | Tidak ada kontrol terpotong atau horizontal overflow |
| Browser | Uji browser desktop dan mobile yang ditargetkan | Kamera/geolocation bekerja atau pesan gagal jelas |
| Queue dan email | Jalankan worker dengan mail transport uji | Notifikasi queued dan laporan diterima |
| PDF/XLSX/CSV | Unduh laporan dengan filter yang sama | Isi konsisten dan file dapat dibuka |
| Scheduler | Jalankan scheduler pada waktu uji | Command terjadwal tercatat tanpa duplikasi |

> [PLACEHOLDER GAMBAR 6.1 — Bukti hasil `php artisan test`: 105 passed, 603 assertions]

> [PLACEHOLDER GAMBAR 6.2 — Bukti pengujian kamera dan GPS pada perangkat target]

> [PLACEHOLDER GAMBAR 6.3 — Bukti pengujian laporan CSV, XLSX, dan PDF]

Status manual tidak dinyatakan lulus sampai skenario tersebut dijalankan ulang dan bukti build aktif disisipkan.

## 6.2 Pembahasan

### 6.2.1 Kesesuaian Arsitektur dengan Kebutuhan

Modular monolith memenuhi kebutuhan proyek saat ini dengan kompleksitas operasional rendah. Ketiga panel dapat memakai model, transaksi, queue, dan basis data yang sama. Application service menjaga logika absensi, merit, gap karier, dan laporan tidak tersebar pada tampilan. Struktur ini belum memenuhi definisi SOA dengan layanan independen karena tidak memiliki kontrak API internal, database terpisah, atau deployment terpisah. Pelaporan arsitektur sebagai modular monolith membuat dokumentasi sesuai dengan implementasi nyata.

### 6.2.2 Keandalan Absensi Dinas

Validasi absensi tidak bergantung pada satu sinyal. Sistem memeriksa hubungan Pegawai dengan penugasan, status dan jadwal, jarak, akurasi GPS, perbedaan waktu, duplikasi, serta bukti foto. Data yang meragukan tidak langsung dibuang, tetapi disimpan dengan alasan pemeriksaan agar HR dapat menilai konteksnya.

Pendekatan tersebut meningkatkan keterlacakan, tetapi tidak menghilangkan seluruh risiko. Browser tidak dapat menjamin bahwa lokasi sistem operasi bebas manipulasi. Foto juga bukan verifikasi biometrik. Karena itu, istilah yang tepat adalah bukti visual dan validasi lokasi berbasis data perangkat, bukan autentikasi identitas mutlak.

### 6.2.3 Objektivitas dan Keterlacakan Merit

Merit menggabungkan empat komponen dengan bobot per periode. Formula tersentralisasi pada `MeritCalculator`, sehingga hasil dari command dan UI mengikuti aturan yang sama. Data wajib diverifikasi dalam dua tahap sebelum publikasi, sedangkan input terkait terkunci setelah hasil final. Activity log menyimpan perhitungan dan transisi penting.

Skor kepatuhan hanya menghitung absensi berstatus `Valid`; `Terlambat` dan `Memerlukan Pemeriksaan` tidak dihitung sebagai tanggal valid. Keputusan ini jelas pada kode, tetapi organisasi tetap perlu menyepakati apakah kebijakan tersebut sesuai kebutuhan operasional. Simulasi bonus juga tidak boleh diperlakukan sebagai transaksi payroll.

### 6.2.4 Keterhubungan Pengembangan Karier

Target jabatan menghubungkan posisi, standar kompetensi, dan kemampuan Pegawai. Gap yang dihasilkan dapat langsung mengarahkan Pegawai atau Atasan ke katalog pelatihan; mentoring menjadi fallback ketika pelatihan yang relevan tidak tersedia. Workflow menjaga proses persetujuan dan penyelesaian tetap berada pada aktor yang tepat.

Hasil merit juga dapat menjadi dasar rekomendasi pelatihan oleh Atasan. Snapshot nilai merit disimpan pada audit log rekomendasi sehingga alasan keputusan tetap dapat ditelusuri walaupun data lain berubah.

### 6.2.5 Integrasi Operasional

Laporan HR menggabungkan absensi, merit, pelatihan, dan mentoring per Pegawai. Ekspor memakai filter serta service yang sama dengan halaman web sehingga mengurangi perbedaan hasil. Database notification, email tertentu, scheduler, audit log, dan backup melengkapi kebutuhan operasional dasar.

Keandalan production tetap bergantung pada konfigurasi di luar kode, terutama queue worker, cron scheduler, mail transport, backup MySQL, HTTPS, kredensial Google Maps, dan DSN monitoring. Pengujian aplikasi tidak menggantikan pemeriksaan infrastruktur tersebut.

### 6.2.6 Hak Akses dan Perlindungan Data

Panel dibatasi berdasarkan peran dan status aktif. Scope data diterapkan pada query serta aksi record, bukan hanya menu. Foto absensi disimpan privat dan dilayani melalui controller yang memeriksa hubungan pengguna. Request perubahan memakai CSRF dan validasi; password di-hash; ekspor dilindungi dari formula injection.

Koordinat, foto, nilai kinerja, komentar, dan catatan mentoring tetap merupakan data sensitif. Retensi foto dan koordinat belum dikelola oleh kebijakan penghapusan terjadwal dalam implementasi aktif, sehingga kebijakan organisasi perlu ditetapkan sebelum penggunaan production.

## 6.3 Keterbatasan Hasil

1. Suite otomatis belum melakukan otomasi browser penuh.
2. Kamera dan GPS perangkat fisik belum dapat disimpulkan hanya dari test server-side.
3. Belum dilakukan load test atau pengukuran kapasitas pengguna serentak.
4. Tidak ada verifikasi wajah, deteksi mock location, atau absensi luring.
5. Tidak ada payroll, pembayaran bonus, atau integrasi perbankan.
6. Tidak ada multi-tenant dan pemisahan data antarorganisasi.
7. Tidak ada REST API publik/independen untuk aplikasi eksternal.
8. Pengiriman email dan pekerjaan queue production membutuhkan konfigurasi serta worker aktif.
9. Backup SQLite yang diuji tidak menggantikan rancangan backup dan restore MySQL production.
10. Pemeriksaan kesiapan publikasi merit memakai daftar Pegawai aktif saat aksi dijalankan, belum memakai snapshot keanggotaan Pegawai pada awal periode.
11. Kebijakan retensi data sensitif dan prosedur pemulihan bencana memerlukan keputusan organisasi.
