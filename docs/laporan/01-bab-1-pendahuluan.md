# BAB I

# PENDAHULUAN

## 1.1 Latar Belakang

Pengelolaan sumber daya manusia membutuhkan data yang akurat, dapat ditelusuri, dan tersedia bagi pihak yang tepat. Pada proses manual atau aplikasi yang terpisah, data penugasan, kehadiran, kinerja, dan pengembangan pegawai sering tidak memiliki hubungan yang jelas. Kondisi tersebut menyulitkan organisasi saat memeriksa keabsahan kehadiran dinas, mengevaluasi kinerja, dan menentukan tindak lanjut pengembangan karier.

Absensi dinas memiliki kebutuhan yang berbeda dari absensi harian di kantor. Pegawai bekerja pada lokasi dan waktu yang ditentukan melalui suatu perintah dinas. Pencatatan kehadiran berbasis waktu saja belum cukup karena tidak membuktikan kedekatan pegawai dengan lokasi tugas. Bukti foto tanpa koordinat juga masih memerlukan pemeriksaan manual yang besar. Karena itu, pencatatan absensi dinas perlu menggabungkan jadwal tugas, koordinat GPS, akurasi pembacaan lokasi, jarak dari titik tugas, waktu pengambilan, dan foto sebagai satu rekam data.

Penilaian kinerja juga membutuhkan dasar yang lebih terukur. Nilai akhir yang hanya berasal dari pendapat satu pihak berisiko tidak menggambarkan capaian pegawai secara menyeluruh. Sistem perlu menggabungkan capaian indikator kinerja utama, kepatuhan terhadap tugas dinas, penilaian Atasan, dan umpan balik rekan. Bobot setiap komponen perlu dapat ditetapkan per periode, sedangkan hasilnya perlu melewati verifikasi sebelum dipublikasikan kepada pegawai.

Pengembangan karier seharusnya mengikuti kesenjangan kompetensi nyata. Pegawai perlu mengetahui perbedaan antara kompetensi yang dimiliki dan standar jabatan tujuan. Atasan serta HR kemudian dapat memakai informasi tersebut untuk memberikan rekomendasi pelatihan atau mentoring yang relevan. Tanpa hubungan antara data kompetensi, hasil merit, pelatihan, dan mentoring, pembinaan karier cenderung bersifat umum dan sulit dievaluasi.

Berdasarkan kebutuhan tersebut, dikembangkan aplikasi web ABL, singkatan dari **Absensi, Benefit, dan Learning**. Aplikasi mengintegrasikan tiga kelompok proses utama:

1. **Absensi**, berupa pengelolaan perintah dinas dan pencatatan kehadiran berbasis GPS serta foto;
2. **Benefit**, berupa sistem merit dan simulasi bonus berdasarkan hasil penilaian;
3. **Learning**, berupa pemetaan kompetensi, target karier, pelatihan, dan mentoring.

Implementasi saat ini menggunakan satu aplikasi Laravel dengan tiga panel berbasis peran: Pegawai, Atasan, dan Admin SDM/HR. Seluruh modul berjalan pada satu basis data dan satu unit deployment. Logika bisnis utama dipisahkan ke dalam service class agar tanggung jawab setiap modul tetap terstruktur. Bentuk ini merupakan **modular monolith dengan application service layer**, bukan kumpulan layanan independen atau REST API yang dapat di-deploy terpisah.

## 1.2 Identifikasi Masalah

Masalah yang menjadi dasar pengembangan sistem adalah sebagai berikut.

### 1.2.1 Validasi Kehadiran Dinas Belum Memadai

Pencatatan kehadiran tanpa hubungan dengan perintah dinas, lokasi, dan waktu tugas tidak cukup untuk memastikan bahwa pegawai hadir pada tempat yang ditentukan. Sistem memerlukan pemeriksaan radius, akurasi GPS, perbedaan waktu perangkat dan server, serta bukti foto yang hanya dapat diakses pengguna berwenang.

### 1.2.2 Penilaian Kinerja Belum Terukur dan Terpadu

Data KPI, kepatuhan dinas, penilaian Atasan, dan umpan balik rekan dapat tersebar pada media berbeda. Akibatnya, perhitungan nilai sulit direproduksi dan keputusan tidak mempunyai jejak audit yang memadai.

### 1.2.3 Pembinaan Karier Belum Berbasis Kesenjangan Kompetensi

Pegawai belum memperoleh gambaran terstruktur mengenai kompetensi yang harus ditingkatkan untuk mencapai jabatan tujuan. Pelatihan dan mentoring juga berisiko diberikan tanpa hubungan langsung dengan kebutuhan kompetensi.

### 1.2.4 Persetujuan dan Publikasi Belum Terkendali

Pengajuan pelatihan, mentoring, verifikasi merit, dan pemeriksaan absensi melibatkan beberapa peran. Tanpa aturan status dan pembatasan aktor, data dapat berubah pada tahap yang tidak semestinya atau disetujui oleh pihak yang tidak berwenang.

### 1.2.5 Data Antarfungsi SDM Belum Terhubung

Keputusan HR membutuhkan pandangan terpadu terhadap organisasi, absensi dinas, merit, pelatihan, dan mentoring. Data yang terpisah menghambat penyusunan laporan dan pelacakan riwayat aktivitas.

## 1.3 Rumusan Masalah

Rumusan masalah proyek ini adalah sebagai berikut.

1. Bagaimana membangun sistem web terintegrasi untuk mengelola organisasi, perintah dinas, absensi dinas, merit, dan pengembangan karier dalam satu aplikasi?
2. Bagaimana memvalidasi absensi dinas menggunakan jadwal tugas, GPS, akurasi lokasi, radius geofencing, waktu perangkat, dan foto tanpa mengklaim verifikasi biometrik?
3. Bagaimana menghitung merit berdasarkan KPI, kepatuhan dinas, penilaian Atasan, dan umpan balik rekan dengan bobot yang dapat ditetapkan per periode?
4. Bagaimana menghubungkan standar jabatan, kompetensi pegawai, target karier, pelatihan, serta mentoring untuk menghasilkan rekomendasi pengembangan?
5. Bagaimana membatasi panel, data, aksi, foto, persetujuan, dan publikasi sesuai peran Pegawai, Atasan, serta HR?
6. Bagaimana menyediakan laporan, ekspor, notifikasi, audit aktivitas, dan backup yang mendukung operasi sistem?

## 1.4 Tujuan Proyek

### 1.4.1 Tujuan Umum

Membangun aplikasi ABL yang mengintegrasikan pengelolaan absensi dinas, sistem merit, dan pembinaan karier dalam satu sistem informasi SDM berbasis web.

### 1.4.2 Tujuan Khusus

1. Menyediakan autentikasi terpusat dan panel sesuai peran pengguna.
2. Menyediakan pengelolaan unit, jabatan, akun, serta hubungan Atasan–Pegawai.
3. Menyediakan pembuatan dan pemantauan perintah dinas berdasarkan bawahan langsung.
4. Mencatat absensi dinas menggunakan lokasi GPS, akurasi, foto kamera, watermark, waktu, dan validasi radius.
5. Menyediakan pemeriksaan HR untuk absensi dengan data lokasi atau waktu yang meragukan.
6. Mengelola periode penilaian, indikator KPI, capaian pegawai, penilaian kinerja, serta hasil merit.
7. Menyediakan verifikasi hasil merit oleh Atasan dan HR sebelum publikasi.
8. Menampilkan simulasi bonus berdasarkan skor merit tanpa memproses pembayaran.
9. Menganalisis kesenjangan kompetensi terhadap jabatan tujuan.
10. Mendukung alur pengajuan, rekomendasi, persetujuan, penolakan, dan penyelesaian pelatihan serta mentoring.
11. Menyediakan laporan HR, ekspor CSV/XLSX/PDF, notifikasi, audit aktivitas, scheduler, dan backup.
12. Memastikan aturan bisnis penting terlindungi oleh validasi, transaksi basis data, pembatasan akses, dan pengujian otomatis.

## 1.5 Manfaat Proyek

### 1.5.1 Manfaat bagi Pegawai

Pegawai memperoleh satu portal untuk melihat tugas dinas, melakukan absensi, memantau KPI dan merit yang telah dipublikasikan, melihat profil kompetensi, menentukan target karier, mengajukan pelatihan, dan mengikuti mentoring.

### 1.5.2 Manfaat bagi Atasan

Atasan dapat mengelola penugasan bawahan langsung, memantau absensi, mencatat KPI, memberikan penilaian, memverifikasi merit, menindaklanjuti pengajuan pelatihan, merekomendasikan pelatihan berdasarkan hasil merit, dan mengelola mentoring.

### 1.5.3 Manfaat bagi HR

HR memperoleh pengelolaan data organisasi dan master, pemeriksaan absensi, pengaturan periode merit, verifikasi serta publikasi hasil, pemeliharaan kompetensi dan katalog pelatihan, laporan lintas modul, ekspor, audit aktivitas, dan dukungan backup.

### 1.5.4 Manfaat Akademis

Proyek menjadi contoh penerapan modular monolith pada aplikasi Laravel yang menggabungkan antarmuka berbasis peran, transaksi untuk workflow, geofencing, perhitungan merit, analisis kompetensi, dan pengujian integrasi.

## 1.6 Batasan Proyek

Batasan sistem berdasarkan implementasi aktif adalah sebagai berikut.

1. Sistem berbentuk aplikasi web untuk satu organisasi dan belum mendukung multi-tenant.
2. Pengguna dibatasi pada tiga peran: Pegawai, Atasan, dan Admin SDM/HR.
3. Absensi yang dikelola adalah absensi perjalanan dinas, bukan presensi harian seluruh pegawai.
4. Absensi memerlukan koneksi internet saat data dikirim. Tidak ada antrean luring, IndexedDB, atau service worker untuk sinkronisasi absensi.
5. Foto digunakan sebagai bukti visual privat. Sistem tidak melakukan pengenalan wajah, pencocokan biometrik, atau deteksi identitas.
6. Sistem memakai Browser Geolocation API dan tidak mendeteksi pemalsuan lokasi pada tingkat sistem operasi perangkat.
7. Google Maps digunakan untuk pencarian dan pemilihan lokasi dinas; ketersediaannya bergantung pada konfigurasi API key dan layanan eksternal Google.
8. Status absensi aktif adalah `Valid`, `Terlambat`, dan `Memerlukan Pemeriksaan`. Lokasi di luar radius menghasilkan status pemeriksaan, bukan status khusus `OutsideRadius`.
9. Penilaian merit memakai KPI, kepatuhan dinas, penilaian Atasan, dan umpan balik rekan. Penilaian Pegawai kepada Atasan disimpan sebagai umpan balik, tetapi tidak menjadi skor rekan bagi Pegawai.
10. Nilai bonus bersifat simulasi berdasarkan dasar bonus periode dan tidak terhubung ke payroll atau transaksi pembayaran.
11. Notifikasi aktif menggunakan database dan email pada kejadian tertentu. Sistem tidak menyediakan Web Push, WhatsApp, WebSocket, atau preferensi kanal per pengguna.
12. Antarmuka menggunakan autentikasi session. Tidak ada REST API independen, Laravel Sanctum, atau login sosial melalui Laravel Socialite.
13. Penyimpanan utama ditujukan untuk MySQL, sedangkan pengujian otomatis memakai SQLite in-memory.
14. Pengujian otomatis mencakup level unit, HTTP, model, service, dan Livewire. Otomasi browser penuh serta pengujian kamera/GPS pada perangkat fisik belum menjadi bagian suite otomatis.

