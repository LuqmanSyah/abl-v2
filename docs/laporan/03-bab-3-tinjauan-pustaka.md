# BAB III

# TINJAUAN PUSTAKA

## 3.1 Kajian Sistem Sejenis

### 3.1.1 Sistem Absensi Berbasis GPS

Sistem absensi berbasis GPS umumnya menggunakan koordinat perangkat untuk menentukan kedekatan pengguna dengan titik kerja. Pola yang lazim adalah menyimpan koordinat lokasi, menetapkan radius, mengambil posisi perangkat, kemudian menghitung jarak antara dua titik. Pendekatan ini lebih kuat daripada pencatatan waktu saja karena menyediakan konteks lokasi.

Namun, koordinat GPS tidak selalu presisi. Hambatan bangunan, kualitas sensor, izin browser, dan kondisi jaringan dapat menghasilkan akurasi yang buruk. Sistem yang baik tidak cukup memeriksa apakah koordinat berada di dalam radius; nilai akurasi dan konsistensi waktu juga perlu dicatat agar data meragukan dapat diperiksa manusia.

ABL menerapkan pola tersebut pada absensi perjalanan dinas. Sistem menggabungkan koordinat, akurasi, jarak, waktu, jadwal penugasan, dan foto kamera. Data yang berada di luar radius, memiliki akurasi buruk, atau menunjukkan perbedaan waktu di luar toleransi ditandai **Memerlukan Pemeriksaan**. ABL tidak melakukan pengenalan wajah dan tidak menganggap foto sebagai bukti biometrik.

### 3.1.2 Sistem Informasi SDM Terintegrasi

Sistem informasi SDM menggabungkan data organisasi, pegawai, operasional, penilaian, dan pengembangan. Nilai utama integrasi bukan sekadar menempatkan semua menu dalam satu aplikasi, tetapi menjaga hubungan data dan aturan proses. Contohnya, perintah dinas harus dibuat oleh Atasan bagi bawahan langsung, hasil merit harus memakai periode yang sama dengan KPI, dan rekomendasi pelatihan harus merujuk pegawai serta hasil merit yang sesuai.

ABL memakai satu model `User` untuk semua peran. Hubungan Atasan–Pegawai disimpan melalui `manager_id`, sedangkan unit dan jabatan menjadi referensi struktur organisasi. Pola ini mengurangi duplikasi identitas antarmodul dan memungkinkan scope data yang konsisten.

### 3.1.3 Sistem Penilaian Kinerja dan Merit

Penilaian berbasis merit menggabungkan ukuran kuantitatif dan evaluasi perilaku. KPI merepresentasikan target serta capaian, data kepatuhan menggambarkan pelaksanaan tugas, dan penilaian manusia memberi konteks yang tidak selalu dapat dihitung otomatis. Agar hasil dapat dipertanggungjawabkan, formula, bobot, sumber data, waktu perhitungan, serta tahap verifikasi harus tersimpan.

ABL menyimpan bobot komponen per periode dan mewajibkan totalnya 100%. Hasil merit merupakan snapshot sehingga nilai yang telah diverifikasi atau dipublikasikan tidak berubah akibat perubahan data berikutnya. Publikasi dilakukan setelah verifikasi Atasan dan HR serta setelah periode berakhir.

### 3.1.4 Sistem Pembinaan Karier

Pembinaan karier berbasis kompetensi membandingkan kemampuan aktual pegawai dengan standar jabatan tujuan. Selisih level menjadi dasar rekomendasi pengembangan. Pelatihan cocok ketika tersedia materi yang berhubungan dengan kompetensi yang kurang, sedangkan mentoring dapat dipakai ketika tidak tersedia pelatihan yang sesuai atau ketika pengembangan membutuhkan pendampingan langsung.

ABL menghubungkan kamus kompetensi, standar kompetensi jabatan, kompetensi pegawai, target jabatan, katalog pelatihan, pengajuan pelatihan, dan mentoring. Sistem menghasilkan analisis gap; keputusan akhir tetap berada pada Pegawai, Atasan, dan HR melalui workflow yang tercatat.

### 3.1.5 Posisi Sistem ABL

| Aspek | Sistem sederhana | Implementasi ABL saat ini |
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

Keuntungan pendekatan ini adalah konsistensi transaksi, deployment sederhana, debugging lebih langsung, dan overhead infrastruktur rendah. Risikonya adalah batas modul dapat kabur bila logika bisnis tersebar. ABL mengurangi risiko tersebut melalui pemisahan controller, model, service, resource Filament, notification, dan command.

### 3.2.2 Application Service

Application service mengoordinasikan satu use case yang dapat melibatkan beberapa model atau sumber daya. Service tidak harus menjadi layanan jaringan. Pada ABL, `AttendanceRecorder` mengoordinasikan penugasan, idempotensi, jarak, status, log, dan notifikasi. `MeritCalculator` mengoordinasikan KPI, absensi, penilaian, formula, hasil, dan notifikasi. Pendekatan ini menjaga controller tetap fokus pada HTTP dan tampilan.

### 3.2.3 Role-Based Access Control dan Record Scope

Role-Based Access Control (RBAC) membatasi kemampuan berdasarkan peran. Dalam aplikasi bisnis, pembatasan menu saja tidak cukup. Query dan aksi pada record juga perlu dibatasi. ABL menerapkan dua tingkat kontrol:

1. akses panel sesuai enum peran dan status akun aktif;
2. scope record berdasarkan kepemilikan atau hubungan organisasi.

Pegawai hanya melihat data sendiri, Atasan melihat data bawahan langsung atau record yang dikelolanya, sedangkan HR melihat data organisasi sesuai tanggung jawab administrasi.

### 3.2.4 Framework Laravel dan Filament

Laravel menyediakan routing, middleware, autentikasi session, validasi, Eloquent ORM, migration, transaction, queue, notification, mail, scheduler, storage, dan testing. Filament membangun panel administrasi di atas Laravel dan Livewire melalui resource, page, form, table, widget, action, serta notification UI.

Pada ABL, kombinasi tersebut memungkinkan sebagian besar antarmuka memakai komponen deklaratif. Halaman absensi tetap dibuat khusus dengan Blade dan JavaScript karena membutuhkan kamera, geolocation, canvas watermark, dan pengiriman berkas.

### 3.2.5 Geofencing dan Rumus Haversine

Geofencing menentukan apakah suatu posisi berada dalam wilayah yang ditetapkan. ABL memakai geofence berbentuk lingkaran yang terdiri atas titik pusat dan radius dalam meter. Jarak permukaan bumi dihitung menggunakan rumus Haversine:

\[
a = \sin^2\left(\frac{\Delta\varphi}{2}\right) +
\cos(\varphi_1)\cos(\varphi_2)\sin^2\left(\frac{\Delta\lambda}{2}\right)
\]

\[
c = 2\arctan2(\sqrt{a}, \sqrt{1-a})
\]

\[
d = R \times c
\]

`\varphi` menyatakan lintang dalam radian, `\lambda` menyatakan bujur, `R` adalah jari-jari bumi, dan `d` adalah jarak dalam meter. Hasil dibandingkan dengan radius snapshot pada perintah dinas.

### 3.2.6 Akurasi GPS dan Pemeriksaan Manual

Geolocation browser mengembalikan estimasi akurasi dalam meter. Angka yang besar menunjukkan posisi kurang pasti. Karena itu, keputusan tidak hanya bergantung pada jarak. ABL memberi status pemeriksaan jika akurasi tidak tersedia atau melewati batas konfigurasi. Pola ini mempertahankan data untuk audit tanpa otomatis menerima informasi yang kualitasnya rendah.

### 3.2.7 Idempotensi dan Transaksi Basis Data

Idempotensi memastikan pengulangan request yang sama tidak membuat record ganda. Saat absensi dikirim, sistem mengunci perintah dinas dan mencari absensi pada tanggal yang sama. Jika record sudah ada, record tersebut dikembalikan dan foto baru dihapus.

Transaksi basis data memastikan perubahan yang saling bergantung berhasil atau gagal sebagai satu kesatuan. `lockForUpdate` digunakan pada alur absensi, merit, pelatihan, dan mentoring untuk mengurangi race condition ketika dua proses mencoba mengubah record yang sama.

### 3.2.8 Penilaian KPI dan Merit

Skor KPI dihitung dari rasio capaian terhadap target untuk setiap indikator. Rasio tiap indikator dibatasi maksimum 120% agar capaian berlebih tetap dihargai tanpa mendominasi total nilai. Skor setiap indikator kemudian dibobotkan.

Skor kepatuhan dinas dihitung dari perbandingan tanggal dinas selesai yang memiliki absensi valid terhadap seluruh tanggal dinas selesai dalam periode. Bila tidak ada dinas selesai, nilai kepatuhan ditetapkan 100. Penilaian Atasan dan umpan balik rekan pada skala 1–5 dinormalisasi menjadi skala 0–100.

### 3.2.9 Analisis Kesenjangan Kompetensi

Analisis gap membandingkan `required_level` pada standar jabatan dengan level aktual pegawai. Nilai selisih positif menunjukkan kebutuhan pengembangan. Sistem mencari pelatihan aktif yang terhubung dengan kompetensi tersebut. Bila tidak ada pelatihan yang sesuai, mentoring menjadi rekomendasi alternatif.

### 3.2.10 Workflow dan State Transition

Workflow membatasi perubahan status berdasarkan keadaan record dan aktor. Contoh alur pelatihan adalah `Menunggu Atasan` → `Menunggu HR` → `Disetujui` → `Selesai`, dengan jalur penolakan dan pengajuan ulang. Setiap transisi memeriksa peran, kepemilikan, status awal, dan aturan waktu.

### 3.2.11 Notifikasi, Queue, dan Scheduler

Notifikasi database menyediakan informasi di dalam panel dan dipolling berkala. Email digunakan pada kejadian tertentu, seperti penugasan dinas, publikasi merit, dan absensi yang memerlukan pemeriksaan. Queue database memindahkan pekerjaan yang sesuai dari request utama. Scheduler menjalankan command berkala untuk kalkulasi, pengingat, laporan, dan backup sesuai konfigurasi aplikasi.

### 3.2.12 Pengujian Kotak Hitam dan Integrasi

Pengujian kotak hitam memeriksa hubungan input, aksi, dan output tanpa bergantung pada detail internal. Pada aplikasi ABL, pengujian otomatis menggunakan Laravel feature test untuk menguji route, model, service, database, serta komponen Livewire. Pengujian integrasi diperlukan karena banyak aturan melibatkan hubungan aktor, status, waktu, dan beberapa tabel sekaligus.

