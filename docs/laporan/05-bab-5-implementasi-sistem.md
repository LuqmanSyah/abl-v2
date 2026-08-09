# BAB V

# IMPLEMENTASI SISTEM

## 5.1 Struktur Implementasi

### 5.1.1 Struktur Aplikasi

Implementasi mengikuti struktur Laravel dengan pembagian utama berikut.

| Lokasi | Tanggung jawab |
| --- | --- |
| `app/Models` | Entity Eloquent, relasi, scope visibilitas, dan invariant data |
| `app/Enums` | Peran, status, tipe penilaian, dan label UI |
| `app/Services` | Logika lintas model seperti absensi, merit, gap karier, dan laporan |
| `app/Filament` | Panel, resource, page, form, table, widget, dan action |
| `app/Http/Controllers` | Login, absensi, foto privat, serta laporan dan ekspor HR |
| `app/Http/Middleware` | Pemeriksaan akun aktif dan request web |
| `app/Notifications` | Notifikasi database dan email |
| `app/Console/Commands` | Kalkulasi merit, pengingat, laporan periodik, dan backup |
| `database/migrations` | Skema, foreign key, index, dan unique constraint |
| `database/seeders` | Master data dan akun bootstrap untuk development |
| `resources/views` | Login, halaman absensi, laporan, PDF, dan email |
| `routes/web.php` | Entry point web berbasis session |
| `tests` | Unit dan feature test |

### 5.1.2 Pola Validasi Aturan Bisnis

Validasi dibagi sesuai tingkat tanggung jawab:

1. **Request validation** memeriksa bentuk input, tipe data, rentang koordinat, ukuran foto, dan nilai wajib.
2. **Panel/resource authorization** membatasi halaman, tombol, dan query berdasarkan peran.
3. **Model invariant** menjaga aturan yang harus berlaku dari semua entry point, misalnya hubungan Atasan–Pegawai dan periode yang telah terkunci.
4. **Application service** menangani aturan use case yang melibatkan beberapa model.
5. **Database constraint** mencegah data tidak konsisten dan duplikasi pada tingkat penyimpanan.

Pelanggaran aturan domain menggunakan `BusinessRuleException`. Komponen UI menerjemahkan exception tersebut menjadi pesan yang dapat dipahami pengguna tanpa menampilkan stack trace.

### 5.1.3 Transaksi dan Penguncian

Alur yang rawan dijalankan bersamaan memakai transaction dan `lockForUpdate`. Contohnya:

- pencatatan absensi mengunci perintah dinas;
- perhitungan merit mengunci periode dan hasil pegawai;
- transisi pelatihan dan mentoring mengambil ulang record dalam keadaan terkunci;
- publikasi mencegah perubahan hasil dan input yang telah final.

Transaksi dicoba ulang sampai tiga kali pada beberapa operasi untuk menangani deadlock sementara.

## 5.2 Implementasi Modul

### 5.2.1 Autentikasi, Peran, dan Organisasi

Seluruh pengguna disimpan pada tabel `users`. Kolom `role` memakai enum `Employee`, `Manager`, atau `Hr`. Method `User::canAccessPanel()` memastikan akun aktif hanya masuk ke panel yang cocok:

- Pegawai ke panel `employee`;
- Atasan ke panel `manager`;
- HR ke panel `hr`.

Relasi organisasi memakai `unit_id`, `position_id`, dan `manager_id`. Saat pengguna disimpan, model memeriksa bahwa jabatan berasal dari unit yang dipilih. Pegawai aktif wajib mempunyai Atasan langsung yang aktif dan berperan Atasan. Atasan yang masih mempunyai bawahan tidak dapat dinonaktifkan atau diubah ke peran lain.

Login memakai session Laravel. Setelah kredensial benar, session diregenerasi dan pengguna diarahkan ke panelnya. Login dibatasi `5` percobaan per menit. Middleware akun aktif mencegah pengguna yang dinonaktifkan tetap memakai session lama.

### 5.2.2 Lokasi dan Perintah Dinas

HR menyimpan lokasi yang sering digunakan pada `duty_locations`, termasuk nama, alamat, lintang, bujur, radius, dan status aktif. Form perintah dinas mengintegrasikan Google Maps untuk pencarian serta pemilihan titik.

Saat Atasan membuat `DutyTrip`, data lokasi disalin ke record dinas sebagai snapshot. Perubahan pada master lokasi setelah itu tidak mengubah penugasan yang telah dibuat. Model memvalidasi bahwa Pegawai merupakan bawahan langsung Atasan. Record menyimpan pegawai, Atasan, tujuan, keperluan, waktu mulai dan selesai, data lokasi, radius, status, serta waktu persetujuan.

Atasan hanya dapat mengubah atau membatalkan dinas miliknya ketika status masih aktif, waktu mulai belum lewat, dan belum terdapat absensi terkait sesuai aturan model. Pegawai hanya melihat tugasnya; Atasan melihat tugas yang dibuatnya; HR dapat memonitor seluruh tugas.

### 5.2.3 Pengambilan Foto dan Lokasi

Halaman absensi memakai API native browser. MediaDevices membuka kamera, Geolocation mengambil posisi berakurasi tinggi, dan Canvas membuat foto akhir. Watermark memuat konteks pegawai, waktu pengambilan, koordinat, dan lokasi tugas sehingga bukti visual dapat dipahami saat diperiksa.

Data yang dikirim ke `AttendanceController` meliputi:

- `captured_at`;
- `latitude` pada rentang -90 sampai 90;
- `longitude` pada rentang -180 sampai 180;
- `accuracy_meters` bila tersedia;
- foto berformat gambar dengan ukuran maksimum 5 MB.

Submit dibatasi `10` request per menit dan dilindungi CSRF. Halaman tidak menyimpan antrean ketika perangkat luring.

### 5.2.4 Pencatatan dan Status Absensi

Controller menyimpan foto ke private local disk lalu memanggil `AttendanceRecorder`. Service menjalankan urutan berikut:

1. mengunci perintah dinas;
2. memastikan pengguna merupakan Pegawai yang ditugaskan;
3. mengembalikan record yang sudah ada untuk dinas dan tanggal yang sama;
4. memastikan status dinas aktif dan jadwal telah dimulai;
5. menghitung jarak Haversine;
6. membandingkan akurasi dengan konfigurasi;
7. membandingkan waktu perangkat dengan waktu server;
8. memilih status;
9. menyimpan absensi dan activity log;
10. memberi notifikasi kepada HR bila perlu diperiksa.

Prioritas penentuan status adalah sebagai berikut.

| Kondisi | Status |
| --- | --- |
| Akurasi tidak tersedia atau melewati batas | Memerlukan Pemeriksaan |
| Jarak lebih besar daripada radius dinas | Memerlukan Pemeriksaan |
| Selisih waktu perangkat dan server melewati toleransi | Memerlukan Pemeriksaan |
| Data lokasi/waktu wajar tetapi pengambilan setelah jadwal selesai | Terlambat |
| Seluruh pemeriksaan terpenuhi | Valid |

Alasan pemeriksaan disimpan pada `review_reason`. HR dapat mengubah status `Memerlukan Pemeriksaan` menjadi `Valid` melalui aksi verifikasi. Jika proses gagal atau request ternyata duplikat, controller menghapus foto baru agar tidak meninggalkan file yatim.

Foto tidak mempunyai URL publik langsung. Endpoint foto memeriksa akun aktif dan memberi akses hanya kepada:

- Pegawai pemilik absensi;
- Atasan yang membuat dinas terkait;
- HR.

### 5.2.5 Sistem KPI dan Merit

HR membuat `ReviewPeriod` yang menyimpan tanggal, status aktif, bobot komponen, dan dasar bonus. Total bobot KPI, kepatuhan dinas, penilaian Atasan, dan umpan balik rekan wajib 100%. Periode aktif tidak boleh tumpang tindih. Indikator KPI berada pada satu periode dan total bobot indikator tidak boleh melebihi 100%; kalkulasi mewajibkan total tepat 100% bila komponen KPI dipakai.

`EmployeeKpi` menyimpan target dan capaian per indikator. Target wajib lebih dari nol dan capaian tidak boleh negatif. Indikator harus berasal dari periode yang sama, sedangkan Pegawai harus merupakan bawahan Atasan yang tercatat.

`PerformanceReview` menyimpan penilaian skala 1–5 dengan tiga tipe:

1. Atasan kepada Pegawai;
2. Pegawai kepada Atasan;
3. Rekan kepada Pegawai dalam unit yang sama.

Penilaian hanya dapat dibuat selama periode menerima review dan tidak dapat diubah atau dihapus setelah dikirim. Kalkulasi merit menggunakan tipe pertama sebagai skor Atasan dan tipe ketiga sebagai umpan balik rekan. Penilaian Pegawai kepada Atasan tetap tersimpan sebagai feedback, tetapi tidak dihitung pada merit Pegawai.

#### Skor KPI

Untuk setiap KPI, rasio capaian dihitung dan dibatasi maksimum `1,2`:

\[
r_i = \min\left(\frac{capaian_i}{target_i}, 1{,}2\right)
\]

\[
skor\_KPI = \frac{\sum(r_i \times bobot\_indikator_i)}{\sum bobot\_indikator_i} \times 100
\]

#### Skor Kepatuhan Dinas

Sistem membentuk himpunan tanggal dari semua dinas berstatus disetujui yang telah selesai dan beririsan dengan periode. Tanggal dengan absensi `Valid` dihitung sebagai tanggal patuh.

\[
skor\_kepatuhan = \min\left(\frac{tanggal\_valid}{seluruh\_tanggal\_dinas} \times 100, 100\right)
\]

Jika tidak ada tanggal dinas selesai, skor kepatuhan bernilai 100.

#### Skor Penilaian

Rata-rata penilaian skala 1–5 dinormalisasi ke 0–100:

\[
skor\_review = \frac{rata\text{-}rata\_nilai}{5} \times 100
\]

#### Skor Total dan Simulasi Bonus

\[
total = \frac{
(KPI \times w_{KPI}) +
(kepatuhan \times w_{kepatuhan}) +
(Atasan \times w_{Atasan}) +
(rekan \times w_{rekan})
}{100}
\]

\[
simulasi\_bonus = dasar\_bonus \times \frac{total}{100}
\]

Bobot tidak ditetapkan tetap pada nilai 40/20/20/20. HR dapat mengubah bobot per periode selama totalnya 100% dan data belum dikunci oleh publikasi.

`MeritCalculator` memeriksa kelengkapan data sesuai bobot aktif. Hasil dapat dihitung ulang sebelum verifikasi. Setelah Atasan memverifikasi atau hasil dipublikasikan, kalkulasi ulang ditolak. Verifikasi Atasan baru tersedia setelah periode berakhir. Aksi HR melakukan verifikasi sekaligus publikasi, tetapi hanya dapat dijalankan setelah seluruh Pegawai aktif mempunyai hasil yang sudah diverifikasi Atasan.

### 5.2.6 Kompetensi dan Target Karier

HR mengelola `Competency` serta `PositionCompetency` untuk menetapkan level wajib per jabatan. `EmployeeCompetency` menyimpan level aktual 1–5, tanggal penilaian, dan catatan. Tanggal penilaian tidak boleh berada di masa depan.

Pegawai dapat memiliki satu `CareerGoal`. Jabatan tujuan wajib mempunyai level lebih tinggi daripada jabatan saat ini. `CareerGapService` membandingkan standar jabatan tujuan dengan kompetensi aktual dan menghasilkan:

- kompetensi yang dinilai;
- level aktual;
- level wajib;
- nilai gap;
- rekomendasi pengembangan.

Pelatihan aktif yang terkait kompetensi diprioritaskan sebagai rekomendasi. Bila tidak ada pelatihan yang sesuai, sistem menyarankan mentoring.

### 5.2.7 Pelatihan dan Mentoring

#### Pelatihan

HR mengelola katalog `Training`, termasuk kompetensi, jadwal, dan status ketersediaan. Pegawai hanya dapat membuat `TrainingRequest` bagi dirinya sendiri dan pelatihan yang masih tersedia. Pengajuan awal berstatus menunggu Atasan. Atasan yang tercatat dapat menyetujui atau menolak. Persetujuan meneruskan record ke HR; HR dapat memverifikasi atau menolak. Pelatihan hanya dapat ditandai selesai setelah tanggal selesai pelatihan terlewati.

Pegawai dapat mengajukan ulang permintaan yang ditolak selama Atasan dan pelatihan masih valid. Atasan juga dapat membuat rekomendasi langsung berdasarkan hasil merit Pegawai yang sudah dipublikasikan. Rekomendasi tersebut masuk ke tahap HR dan menyimpan snapshot komponen merit pada activity log.

#### Mentoring

Pegawai mengajukan `Mentoring` kepada Atasan langsung untuk tanggal yang tidak berada di masa lalu. Atasan dapat menyetujui, menolak, menjadwalkan, dan menyelesaikan sesi sesuai status. Penyelesaian hanya dapat dilakukan setelah jadwal sesi dan harus menyimpan hasil atau tindak lanjut yang dibutuhkan workflow.

Kedua modul memakai transaksi dan row lock pada perubahan status untuk mencegah dua aksi bersamaan menghasilkan state yang bertentangan.

### 5.2.8 Notifikasi dan Email

Notifikasi utama yang tersedia adalah:

- `TripAssigned`;
- `AttendanceNeedsReview`;
- `KpiDeadlineReminder`;
- `MeritReadyForVerification`;
- `MeritPublished`;
- `TrainingPending`;
- `MentoringPending`;
- `MentoringScheduled`.

Database notification ditampilkan pada Filament dan dipolling setiap 30 detik. Penugasan dinas, publikasi merit, serta absensi yang memerlukan pemeriksaan juga dapat memakai email. Queue lokal memakai driver database dan mail lokal memakai driver log. Pada production, queue worker dan mail transport harus dikonfigurasi agar pekerjaan antrean benar-benar terkirim.

### 5.2.9 Laporan, Ekspor, dan Audit

`HrReportService` menyusun ringkasan per Pegawai dari data absensi, merit, pelatihan, dan mentoring. `HrReportController` menyediakan filter periode, unit, jabatan, serta pilihan kolom. Kolom yang tersedia meliputi identitas Pegawai, organisasi, jumlah absensi, absensi valid, skor merit, jumlah/penyelesaian pelatihan, dan jumlah/penyelesaian mentoring.

Ekspor memakai sumber query dan filter yang sama dengan halaman web:

- CSV melalui `league/csv`;
- XLSX melalui OpenSpout;
- PDF melalui DomPDF.

CSV dan XLSX menggunakan streamed response. Teks berawalan `=`, `+`, `-`, `@`, tab, atau carriage return diawali apostrof untuk mencegah formula injection saat dibuka pada spreadsheet.

`ActivityLog` mencatat perubahan serta transisi penting secara polymorphic. HR memperoleh halaman audit read-only. Log menyediakan konteks aktor, subjek, aksi, waktu, dan data tambahan yang relevan.

### 5.2.10 Command, Scheduler, dan Backup

Command aplikasi mendukung:

- perhitungan merit per periode;
- pengingat KPI;
- pengiriman laporan periodik kepada HR aktif;
- backup basis data pada lingkungan yang didukung.

`merit:send-report` menerima filter periode, unit, dan jabatan, memakai `HrReportService`, lalu mengirim hasil kepada pengguna HR aktif. Backup SQLite diuji dengan pemeriksaan bahwa berkas hasil valid dan dapat dipulihkan. Deployment MySQL tetap membutuhkan strategi backup server/database yang sesuai lingkungan operasi.

## 5.3 Tangkapan Layar Implementasi

Placeholder berikut sengaja disediakan untuk diganti dengan tangkapan layar build aktif. Setiap gambar harus menampilkan data dummy dan tidak memuat kata sandi, token, alamat email pribadi, koordinat sensitif, atau foto pengguna nyata.

### 5.3.1 Halaman Login

> [PLACEHOLDER GAMBAR 5.1 — Login terpusat Sistem SDM pada desktop dan mobile]

### 5.3.2 Panel Pegawai

> [PLACEHOLDER GAMBAR 5.2 — Dashboard Pegawai dan widget dinas aktif]

### 5.3.3 Halaman Absensi Dinas

> [PLACEHOLDER GAMBAR 5.3 — Kamera, pratinjau watermark, data GPS, dan tombol kirim]

### 5.3.4 Panel Atasan

> [PLACEHOLDER GAMBAR 5.4 — Dashboard Atasan, daftar bawahan, dan tugas yang perlu diproses]

### 5.3.5 Panel HR

> [PLACEHOLDER GAMBAR 5.5 — Dashboard HR, statistik organisasi, dan navigasi modul]

### 5.3.6 Laporan HR

> [PLACEHOLDER GAMBAR 5.6 — Filter laporan, pilihan kolom, tabel, dan tombol ekspor]
