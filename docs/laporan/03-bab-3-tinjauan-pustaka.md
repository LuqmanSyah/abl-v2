# BAB III

# TINJAUAN PUSTAKA

## 3.1 Kajian Sistem Sejenis

### 3.1.1 Sistem Absensi Berbasis GPS

Sistem absensi berbasis GPS umumnya menggunakan koordinat perangkat untuk menentukan kedekatan pengguna dengan titik kerja. Pola yang lazim adalah menyimpan koordinat lokasi, menetapkan radius, mengambil posisi perangkat, kemudian menghitung jarak antara dua titik. Pendekatan ini lebih kuat daripada pencatatan waktu saja karena menyediakan konteks lokasi.

Namun, koordinat GPS tidak selalu presisi. Hambatan bangunan, kualitas sensor, izin browser, dan kondisi jaringan dapat menghasilkan akurasi yang buruk. Sistem yang baik tidak cukup memeriksa apakah koordinat berada di dalam radius; nilai akurasi dan konsistensi waktu juga perlu dicatat agar data meragukan dapat diperiksa manusia.

Sistem SDM menerapkan pola tersebut pada absensi perjalanan dinas. Sistem menggabungkan koordinat, akurasi, jarak, waktu, jadwal penugasan, dan foto kamera. Data yang berada di luar radius, memiliki akurasi buruk, atau menunjukkan perbedaan waktu di luar toleransi ditandai **Memerlukan Pemeriksaan**. Sistem SDM tidak melakukan pengenalan wajah dan tidak menganggap foto sebagai bukti biometrik.

### 3.1.2 Sistem Informasi SDM Terintegrasi

Sistem informasi SDM menggabungkan data organisasi, pegawai, operasional, penilaian, dan pengembangan. Nilai utama integrasi bukan sekadar menempatkan semua menu dalam satu aplikasi, tetapi menjaga hubungan data dan aturan proses. Contohnya, perintah dinas harus dibuat oleh Atasan bagi bawahan langsung, hasil merit harus memakai periode yang sama dengan KPI, dan rekomendasi pelatihan harus merujuk pegawai serta hasil merit yang sesuai.

Sistem SDM memakai satu model `User` untuk semua peran. Hubungan Atasan–Pegawai disimpan melalui `manager_id`, sedangkan unit dan jabatan menjadi referensi struktur organisasi. Pola ini mengurangi duplikasi identitas antarmodul dan memungkinkan scope data yang konsisten.

### 3.1.3 Sistem Penilaian Kinerja dan Merit

Penilaian berbasis merit menggabungkan ukuran kuantitatif dan evaluasi perilaku. KPI merepresentasikan target serta capaian, data kepatuhan menggambarkan pelaksanaan tugas, dan penilaian manusia memberi konteks yang tidak selalu dapat dihitung otomatis. Agar hasil dapat dipertanggungjawabkan, formula, bobot, sumber data, waktu perhitungan, serta tahap verifikasi harus tersimpan.

Sistem SDM menyimpan bobot komponen per periode dan mewajibkan totalnya 100%. Hasil merit merupakan snapshot sehingga nilai yang telah diverifikasi atau dipublikasikan tidak berubah akibat perubahan data berikutnya. Publikasi dilakukan setelah verifikasi Atasan dan HR serta setelah periode berakhir.

### 3.1.4 Sistem Pembinaan Karier

Pembinaan karier berbasis kompetensi membandingkan kemampuan aktual pegawai dengan standar jabatan tujuan. Selisih level menjadi dasar rekomendasi pengembangan. Pelatihan cocok ketika tersedia materi yang berhubungan dengan kompetensi yang kurang, sedangkan mentoring dapat dipakai ketika tidak tersedia pelatihan yang sesuai atau ketika pengembangan membutuhkan pendampingan langsung.

Sistem SDM menghubungkan kamus kompetensi, standar kompetensi jabatan, kompetensi pegawai, target jabatan, katalog pelatihan, pengajuan pelatihan, dan mentoring. Sistem menghasilkan analisis gap; keputusan akhir tetap berada pada Pegawai, Atasan, dan HR melalui workflow yang tercatat.

### 3.1.5 Posisi Sistem SDM

| Aspek | Sistem sederhana | Implementasi Sistem SDM saat ini |
| --- | --- | --- |
| Absensi | Waktu atau koordinat saja | Jadwal dinas, GPS, akurasi, radius, waktu, foto, watermark, pemeriksaan HR |
| Struktur pengguna | Akun tanpa relasi organisasi | Unit, jabatan, peran, Atasan langsung, dan scope data |
| Penilaian | Nilai tunggal | KPI, kepatuhan dinas, penilaian Atasan, umpan balik rekan, bobot periode |
| Publikasi hasil | Langsung terlihat | Verifikasi Atasan dan HR, lalu publikasi setelah periode berakhir |
| Karier | Daftar pelatihan | Target jabatan, gap kompetensi, rekomendasi pelatihan/mentoring |
| Pelaporan | Rekap satu modul | Ringkasan lintas absensi, merit, pelatihan, mentoring; CSV/XLSX/PDF |
| Audit | Riwayat terbatas | Activity log untuk perubahan dan transisi penting |

## 3.2 Landasan Teori

### 3.2.1 Modular Monolith

Modular monolith adalah aplikasi yang dirilis sebagai satu unit tetapi dibagi menjadi modul dengan tanggung jawab jelas. Berbeda dari microservices atau SOA terdistribusi, komunikasi antarmodul terjadi melalui pemanggilan kode dalam proses yang sama dan umumnya memakai basis data yang sama.

Keuntungan pendekatan ini adalah konsistensi transaksi, deployment sederhana, debugging lebih langsung, dan overhead infrastruktur rendah. Risikonya adalah batas modul dapat kabur bila logika bisnis tersebar. Sistem SDM mengurangi risiko tersebut melalui pemisahan controller, model, service, resource Filament, notification, dan command.

### 3.2.2 Application Service

Application service mengoordinasikan satu use case yang dapat melibatkan beberapa model atau sumber daya. Service tidak harus menjadi layanan jaringan. Pada Sistem SDM, `AttendanceRecorder` mengoordinasikan penugasan, idempotensi, jarak, status, log, dan notifikasi. `MeritCalculator` mengoordinasikan KPI, absensi, penilaian, formula, hasil, dan notifikasi. Pendekatan ini menjaga controller tetap fokus pada HTTP dan tampilan.

Pemisahan tersebut membuat aturan bisnis dapat diuji secara langsung tanpa melalui antarmuka web. Apabila kebijakan berubah, perbaikan cukup dilakukan pada satu service sehingga risiko ketidakkonsistenan logika antarmodul dapat ditekan.

### 3.2.3 Role-Based Access Control dan Record Scope

Role-Based Access Control (RBAC) membatasi kemampuan berdasarkan peran. Dalam aplikasi bisnis, pembatasan menu saja tidak cukup. Query dan aksi pada record juga perlu dibatasi. Sistem SDM menerapkan dua tingkat kontrol:

1. akses panel sesuai enum peran dan status akun aktif;
2. scope record berdasarkan kepemilikan atau hubungan organisasi.

Pegawai hanya melihat data sendiri, Atasan melihat data bawahan langsung atau record yang dikelolanya, sedangkan HR melihat data organisasi sesuai tanggung jawab administrasi.

### 3.2.4 Framework Laravel dan Filament

Laravel menyediakan routing, middleware, autentikasi session, validasi, Eloquent ORM, migration, transaction, queue, notification, mail, scheduler, storage, dan testing. Filament membangun panel administrasi di atas Laravel dan Livewire melalui resource, page, form, table, widget, action, serta notification UI.

Pada Sistem SDM, kombinasi tersebut memungkinkan sebagian besar antarmuka memakai komponen deklaratif. Halaman absensi tetap dibuat khusus dengan Blade dan JavaScript karena membutuhkan kamera, geolocation, canvas watermark, dan pengiriman berkas.

### 3.2.5 Geofencing dan Rumus Haversine

Geofencing menentukan apakah suatu posisi berada dalam wilayah yang ditetapkan. Sistem SDM memakai geofence berbentuk lingkaran yang terdiri atas titik pusat dan radius dalam meter. Jarak permukaan bumi dihitung menggunakan rumus Haversine. Rumus dihitung bertahap dengan tiga langkah berikut.

**Langkah 1 — menghitung nilai `a`.**

`a = (sin(Δφ/2))² + cos(φ₁) × cos(φ₂) × (sin(Δλ/2))²`

Keterangan:
- `Δφ` adalah selisih lintang kedua titik dalam radian;
- `Δλ` adalah selisih bujur kedua titik dalam radian;
- `φ₁` adalah lintang titik pertama dalam radian;
- `φ₂` adalah lintang titik kedua dalam radian;
- fungsi `sin` dan `cos` adalah fungsi trigonometri sinus dan kosinus.

**Langkah 2 — menghitung nilai `c`.**

`c = 2 × arctan2(√a, √(1 − a))`

Keterangan:
- `√a` adalah akar kuadrat dari `a`;
- `arctan2` adalah fungsi arctangen dua argumen;
- hasil `c` merupakan sudut sentral antara dua titik dalam radian.

**Langkah 3 — menghitung jarak `d` dalam meter.**

`d = R × c`

Keterangan:
- `R` adalah jari-jari rata-rata bumi, yaitu 6.371.000 meter;
- hasil `d` adalah jarak antara dua titik dalam meter.

Hasil `d` kemudian dibandingkan dengan radius snapshot pada perintah dinas untuk menentukan apakah pegawai berada di dalam wilayah tugas.

### 3.2.6 Akurasi GPS dan Pemeriksaan Manual

Geolocation browser mengembalikan estimasi akurasi dalam meter. Angka yang besar menunjukkan posisi kurang pasti. Oleh karena itu, keputusan tidak hanya bergantung pada jarak. Sistem SDM memberi status pemeriksaan jika akurasi tidak tersedia atau melewati batas konfigurasi. Pola ini mempertahankan data untuk audit tanpa otomatis menerima informasi yang kualitasnya rendah.

Dengan demikian, data yang meragukan tidak langsung dibuang melainkan disimpan bersama alasan pemeriksaan. Pendekatan ini memungkinkan HR mempertimbangkan konteks sebelum menetapkan status final, sekaligus menjaga objektivitas proses.

### 3.2.7 Idempotensi dan Transaksi Basis Data

Idempotensi memastikan pengulangan request yang sama tidak membuat record ganda. Saat absensi dikirim, sistem mengunci perintah dinas dan mencari absensi pada tanggal yang sama. Jika record sudah ada, record tersebut dikembalikan dan foto baru dihapus.

Transaksi basis data memastikan perubahan yang saling bergantung berhasil atau gagal sebagai satu kesatuan. `lockForUpdate` digunakan pada alur absensi, merit, pelatihan, dan mentoring untuk mengurangi race condition ketika dua proses mencoba mengubah record yang sama.

### 3.2.8 Penilaian KPI dan Merit

Skor KPI dihitung dari rasio capaian terhadap target untuk setiap indikator. Rasio tiap indikator dibatasi maksimum 120% agar capaian berlebih tetap dihargai tanpa mendominasi total nilai. Skor setiap indikator kemudian dibobotkan.

Skor kepatuhan dinas dihitung dari perbandingan tanggal dinas selesai yang memiliki absensi valid terhadap seluruh tanggal dinas selesai dalam periode. Bila tidak ada dinas selesai, nilai kepatuhan ditetapkan 100. Penilaian Atasan dan umpan balik rekan pada skala 1–5 dinormalisasi menjadi skala 0–100.

### 3.2.9 Analisis Kesenjangan Kompetensi

Analisis gap membandingkan `required_level` pada standar jabatan dengan level aktual pegawai. Nilai selisih positif menunjukkan kebutuhan pengembangan. Sistem mencari pelatihan aktif yang terhubung dengan kompetensi tersebut. Bila tidak ada pelatihan yang sesuai, mentoring menjadi rekomendasi alternatif.

Logika pemilihan rekomendasi tersebut menjadikan pengembangan karier berbasis data. Setiap rekomendasi dapat ditelusuri kembali ke kompetensi yang kurang, sehingga keputusan pelatihan atau mentoring tidak lagi bergantung pada asumsi subjektif semata.

### 3.2.10 Workflow dan State Transition

Workflow membatasi perubahan status berdasarkan keadaan record dan aktor. Contoh alur pelatihan adalah `Menunggu Atasan` → `Menunggu HR` → `Disetujui` → `Selesai`, dengan jalur penolakan dan pengajuan ulang. Setiap transisi memeriksa peran, kepemilikan, status awal, dan aturan waktu.

Pembatasan tersebut memberi jejak audit pada setiap perubahan tahap dan mencegah transisi yang tidak sah. Ketika dua pengguna mencoba mengubah record dalam waktu bersamaan, hubungan transisi dan penguncian memastikan hanya satu hasil yang berlaku dan dicatat.

### 3.2.11 Notifikasi, Queue, dan Scheduler

Notifikasi database menyediakan informasi di dalam panel dan dipolling berkala. Email digunakan pada kejadian tertentu, seperti penugasan dinas, publikasi merit, dan absensi yang memerlukan pemeriksaan. Queue database memindahkan pekerjaan yang sesuai dari request utama. Scheduler menjalankan command berkala untuk kalkulasi, pengingat, laporan, dan backup sesuai konfigurasi aplikasi.

Kombinasi ketiganya memisahkan pekerjaan berat dari permintaan interaktif sehingga antarmuka tetap responsif. Scheduler memastikan proses periodik, seperti kalkulasi merit dan pengingat KPI, berjalan tanpa campur tangan manual setiap siklus.

### 3.2.12 Pengujian Kotak Hitam dan Integrasi

Pengujian kotak hitam memeriksa hubungan input, aksi, dan output tanpa bergantung pada detail internal. Pada Sistem SDM, pengujian otomatis menggunakan Laravel feature test untuk menguji route, model, service, database, serta komponen Livewire. Pengujian integrasi diperlukan karena banyak aturan melibatkan hubungan aktor, status, waktu, dan beberapa tabel sekaligus.

Cakupan tersebut menempatkan aturan bisnis yang paling rawan — absensi, merit, pelatihan, dan mentoring — di bawah perlindungan yang dapat dijalankan ulang secara berkala. Setiap perubahan kode dapat diuji kembali dengan cepat untuk mendeteksi regresi pada alur lama.

## 3.3 Definisi Istilah Penting

| Istilah | Definisi dalam konteks laporan |
| --- | --- |
| Abu berjalan (dinas) | Penugasan di luar kantor yang ditetapkan melalui perintah dinas oleh Atasan |
| Absensi valid | Kehadiran dinas yang memenuhi radius, akurasi GPS, dan toleransi waktu |
| Aplikasi layanan (application service) | Kelas yang mengoordinasikan satu use case yang melibatkan beberapa model |
| As-built | Keadaan implementasi yang benar-benar berjalan pada build aktif |
| Atasan | Pengguna berperan Manager yang mengelola bawahan langsung |
| Biometrik | Pengenalan identitas wajah/jari; tidak digunakan pada sistem |
| Geofencing | Batas wilayah berbentuk lingkaran dari titik pusat dan radius |
| Rumus Haversine | Formula jarak dua titik di permukaan bumi |
| Idempotensi | Pengulangan request yang sama tidak membuat data ganda |
| Kepatuhan dinas | Perbandingan tanggal dinas selesai yang memiliki absensi valid |
| KPI | Key Performance Indicator, target dan capaian terukur pegawai |
| Memerlukan Pemeriksaan | Status absensi yang datanya meragukan dan perlu penilaian HR |
| Merit | Sistem penilaian kinerja dari empat komponen berbobot per periode |
| Modular monolith | Satu aplikasi yang dibagi modul bertanggung jawab jelas |
| Panel | Antarmuka Filament khusus peran (Pegawai, Atasan, HR) |
| RAD | Rapid Application Development, metode pengembangan iteratif |
| RBAC | Role-Based Access Control, kontrol akses berbasis peran |
| Record scope | Pembatasan query dan aksi berdasarkan kepemilikan/hubungan |
| Scheduler | Penjadwal command berkala di Laravel |
| Snapshot | Salinan nilai yang menjadi dasar hasil agar tidak berubah |
| Workflow | Aturan perubahan status yang dibatasi aktor dan kondisi |

---
