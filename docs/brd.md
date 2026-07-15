# BUSINESS REQUIREMENTS DOCUMENT

## SISTEM SUMBER DAYA MANUSIA

**Nama Proyek:** Sistem Sumber Daya Manusia
**Modul:** Absensi Dinas, Sistem Merit, dan Pembinaan Karier
**Versi:** 1.2

---

# 1. Pendahuluan

## 1.1 Tujuan Dokumen

Dokumen ini menjelaskan kebutuhan bisnis dan fungsi utama Sistem Sumber Daya Manusia sebagai acuan bagi pihak HR, manajemen, dan tim pengembang.

## 1.2 Tujuan Sistem

Sistem dikembangkan untuk:

1. Memastikan absensi dinas dilakukan pada lokasi yang benar.
2. Mengurangi manipulasi lokasi dan bukti kehadiran.
3. Membuat penilaian kinerja lebih objektif dan transparan.
4. Membantu pegawai mengetahui jalur karier dan kompetensi yang perlu dikembangkan.
5. Mempermudah pengelolaan pelatihan dan mentoring.

## 1.3 Ruang Lingkup

Sistem mencakup tiga layanan utama:

1. **Absensi Dinas**

   * Pengajuan dinas.
   * Pemilihan lokasi melalui Google Maps.
   * Persetujuan Atasan.
   * Absensi menggunakan GPS dan foto langsung.
   * Validasi radius lokasi.
   * Penyimpanan absensi secara luring.

2. **Sistem Merit**

   * Penetapan dan pemantauan KPI.
   * Penilaian 360 derajat.
   * Perhitungan skor merit.
   * Informasi estimasi bonus atau insentif.

3. **Pembinaan Karier**

   * Jalur karier.
   * Analisis kesenjangan kompetensi.
   * Pengajuan pelatihan.
   * Penjadwalan dan pencatatan mentoring.

## 1.4 Batasan Sistem

1. Sistem hanya menangani absensi untuk dinas yang telah disetujui.
2. Sistem hanya menampilkan estimasi bonus dan tidak memproses pembayaran.
3. Mentor dalam sistem merupakan Atasan.
4. Sistem tidak menggantikan sistem penggajian.
5. Validasi lokasi bergantung pada GPS perangkat pengguna.

---

# 2. Aktor Sistem

## 2.1 Pegawai

Pegawai dapat:

* Mengajukan dinas.
* Memilih lokasi dinas melalui Google Maps.
* Melakukan absensi menggunakan GPS dan kamera.
* Melihat KPI dan skor merit.
* Mengisi penilaian 360 derajat.
* Melihat jalur karier dan kesenjangan kompetensi.
* Mengajukan pelatihan.
* Mengajukan mentoring.

## 2.2 Atasan

Atasan dapat:

* Menyetujui atau menolak pengajuan dinas.
* Melihat lokasi dinas pada peta.
* Memantau absensi Pegawai.
* Menetapkan dan menilai KPI.
* Mengisi penilaian kinerja.
* Menyetujui pelatihan.
* Menyetujui jadwal dan mencatat hasil mentoring.

## 2.3 Admin SDM/HR

Admin SDM/HR dapat:

* Mengelola data Pegawai dan akun.
* Mengelola jabatan dan struktur organisasi.
* Mengelola lokasi dinas dan radius geofencing.
* Mengelola indikator KPI dan formula merit.
* Mengelola kompetensi dan jalur karier.
* Mengelola katalog pelatihan.
* Memantau absensi, kinerja, dan pembinaan karier.
* Membuat laporan.

---

# 3. Kebutuhan Bisnis

## BR-01

Sistem harus mencegah kecurangan absensi dinas melalui validasi GPS, radius lokasi, waktu, dan foto langsung.

## BR-02

Sistem harus mempermudah proses pengajuan dan persetujuan dinas.

## BR-03

Sistem harus meningkatkan objektivitas penilaian kinerja berdasarkan KPI, kedisiplinan, dan penilaian 360 derajat.

## BR-04

Sistem harus memberikan informasi yang transparan mengenai skor merit dan estimasi penghargaan.

## BR-05

Sistem harus membantu Pegawai mengetahui jalur karier dan kekurangan kompetensinya.

## BR-06

Sistem harus mendukung proses pelatihan dan mentoring secara terstruktur.

---

# 4. Kebutuhan Fungsional

## 4.1 Manajemen Pengguna

### FR-USR-01

Sistem harus menyediakan fitur masuk dan keluar.

### FR-USR-02

Sistem harus membatasi akses berdasarkan peran Pegawai, Atasan, dan Admin SDM/HR.

### FR-USR-03

Admin SDM/HR harus dapat mengelola akun dan data Pegawai.

---

## 4.2 Absensi Dinas

### FR-ABS-01 — Pengajuan Dinas

Pegawai dapat mengisi pengajuan dinas yang memuat:

* Tujuan dinas;
* Tanggal dan waktu;
* Keperluan;
* Lokasi tujuan;
* Dokumen pendukung jika diperlukan.

### FR-ABS-02 — Map Picker

Sistem harus menyediakan Google Maps Map Picker agar Pegawai dapat:

* Mencari lokasi berdasarkan nama atau alamat;
* Memilih titik lokasi pada peta;
* Memindahkan penanda lokasi;
* Menyimpan latitude dan longitude.

### FR-ABS-03 — Lokasi Dinas Terdaftar

Admin SDM/HR dapat menyimpan lokasi yang sering digunakan beserta:

* Nama lokasi;
* Alamat;
* Latitude;
* Longitude;
* Radius geofencing.

### FR-ABS-04 — Persetujuan Dinas

Atasan dapat melihat detail dan titik lokasi pada peta, kemudian menyetujui atau menolak pengajuan.

### FR-ABS-05 — Penguncian Lokasi

Setelah pengajuan disetujui, Pegawai tidak dapat mengubah lokasi dinas.

### FR-ABS-06 — Validasi GPS

Saat absensi, sistem harus mengambil koordinat aktual Pegawai.

### FR-ABS-07 — Validasi Radius

Sistem harus menghitung jarak antara posisi Pegawai dan lokasi dinas yang telah disetujui.

Absensi dinyatakan valid apabila Pegawai berada di dalam radius yang ditentukan.

### FR-ABS-08 — Foto Langsung

Pegawai wajib mengambil foto melalui kamera perangkat dan tidak dapat mengunggah foto dari galeri.

### FR-ABS-09 — Watermark

Foto absensi harus memuat:

* Nama Pegawai;
* Tanggal;
* Waktu;
* Koordinat;
* Nama atau alamat lokasi.

### FR-ABS-10 — Validasi Waktu

Sistem harus memeriksa apakah absensi dilakukan sesuai jadwal dinas.

### FR-ABS-11 — Penyimpanan Luring

Jika tidak ada internet, data absensi disimpan sementara pada perangkat.

### FR-ABS-12 — Sinkronisasi

Data absensi luring dikirim otomatis ketika koneksi internet tersedia.

### FR-ABS-13 — Riwayat Absensi

Pegawai, Atasan, dan Admin SDM/HR dapat melihat riwayat absensi sesuai hak akses.

---

## 4.3 Sistem Merit

### FR-MRT-01 — Periode Penilaian

Admin SDM/HR dapat membuat periode penilaian kinerja.

### FR-MRT-02 — Indikator KPI

Admin SDM/HR dapat mengatur indikator dan bobot KPI.

### FR-MRT-03 — Penetapan KPI

Atasan dapat menetapkan target KPI kepada Pegawai.

### FR-MRT-04 — Pemantauan KPI

Pegawai dan Atasan dapat melihat target serta capaian KPI.

### FR-MRT-05 — Penilaian 360 Derajat

Sistem menyediakan penilaian dari:

* Atasan kepada Pegawai;
* Pegawai kepada Atasan;
* Pegawai kepada rekan kerja.

### FR-MRT-06 — Perhitungan Merit

Sistem menghitung skor merit berdasarkan:

* Capaian KPI;
* Kedisiplinan;
* Penilaian Atasan;
* Penilaian 360 derajat.

### FR-MRT-07 — Hasil Merit

Pegawai dapat melihat:

* Skor merit;
* Komponen penilaian;
* Estimasi bonus atau insentif.

### FR-MRT-08 — Verifikasi

Hasil penilaian harus diverifikasi oleh Atasan dan Admin SDM/HR sebelum ditampilkan kepada Pegawai.

---

## 4.4 Pembinaan Karier

### FR-KAR-01 — Jalur Karier

Sistem harus menampilkan jabatan Pegawai saat ini dan jabatan yang dapat dicapai.

### FR-KAR-02 — Standar Kompetensi

Admin SDM/HR dapat menentukan kompetensi yang dibutuhkan untuk setiap jabatan.

### FR-KAR-03 — Analisis Kesenjangan

Sistem membandingkan kompetensi Pegawai dengan kompetensi jabatan tujuan.

### FR-KAR-04 — Rekomendasi

Sistem menampilkan kompetensi yang belum terpenuhi dan rekomendasi pengembangannya.

### FR-KAR-05 — Katalog Pelatihan

Admin SDM/HR dapat mengelola pelatihan internal dan eksternal.

### FR-KAR-06 — Pengajuan Pelatihan

Pegawai dapat mengajukan pelatihan yang tersedia atau direkomendasikan.

### FR-KAR-07 — Persetujuan Pelatihan

Atasan dapat menyetujui atau menolak pengajuan pelatihan.

### FR-KAR-08 — Verifikasi Pelatihan

Admin SDM/HR dapat memverifikasi dan mencatat hasil pelatihan.

### FR-KAR-09 — Mentoring

Pegawai dapat mengajukan jadwal mentoring kepada Atasan.

### FR-KAR-10 — Catatan Mentoring

Atasan dapat mencatat:

* Topik;
* Target;
* Hasil diskusi;
* Tindak lanjut.

---

# 5. Alur Proses Bisnis

## 5.1 Pengajuan Dinas

1. Pegawai mengisi formulir pengajuan dinas.
2. Pegawai memilih lokasi melalui Google Maps Map Picker.
3. Sistem menyimpan alamat, latitude, dan longitude.
4. Pegawai mengirim pengajuan.
5. Atasan melihat detail dan lokasi pada peta.
6. Atasan menyetujui atau menolak pengajuan.
7. Jika disetujui, lokasi dikunci.
8. Pegawai menerima informasi hasil pengajuan.

**Alur singkat:**

**Pegawai mengajukan dinas → memilih lokasi → Atasan memeriksa → Atasan menyetujui atau menolak.**

---

## 5.2 Absensi Dinas

1. Pegawai membuka dinas yang telah disetujui.
2. Sistem mengambil lokasi GPS Pegawai.
3. Sistem membandingkan posisi Pegawai dengan lokasi dinas.
4. Sistem menghitung jarak dan memeriksa radius.
5. Jika berada di dalam radius, Pegawai dapat mengambil foto.
6. Sistem menambahkan watermark.
7. Sistem menyimpan data absensi.
8. Jika tidak ada internet, data disimpan secara lokal.
9. Data disinkronkan ketika internet tersedia.
10. Atasan dan Admin SDM/HR dapat memantau hasil absensi.

**Alur singkat:**

**Sistem mengambil GPS → memvalidasi radius → Pegawai mengambil foto → data absensi disimpan.**

---

## 5.3 Penilaian KPI dan Merit

1. Admin SDM/HR membuat periode dan indikator penilaian.
2. Atasan menetapkan KPI Pegawai.
3. Pegawai menjalankan target KPI.
4. Atasan memperbarui capaian KPI.
5. Pegawai dan Atasan mengisi penilaian 360 derajat.
6. Sistem mengambil data KPI, absensi, dan penilaian.
7. Sistem menghitung skor merit.
8. Atasan dan Admin SDM/HR memverifikasi hasil.
9. Pegawai melihat skor dan estimasi penghargaan.

**Alur singkat:**

**HR mengatur penilaian → Atasan menetapkan KPI → sistem menghitung merit → Pegawai melihat hasil.**

---

## 5.4 Pembinaan Karier

1. Admin SDM/HR menentukan jabatan dan standar kompetensi.
2. Pegawai memilih jabatan tujuan.
3. Sistem membandingkan kompetensi Pegawai dengan jabatan tujuan.
4. Sistem menampilkan kesenjangan kompetensi.
5. Sistem memberikan rekomendasi pelatihan atau mentoring.
6. Pegawai mengajukan pelatihan atau mentoring.
7. Atasan memberikan persetujuan.
8. Admin SDM/HR mencatat hasil pengembangan.

**Alur singkat:**

**Pegawai memilih target karier → sistem menganalisis kompetensi → Pegawai mengikuti pelatihan atau mentoring.**

---

# 6. Status Utama Sistem

## 6.1 Status Pengajuan Dinas

* Menunggu Persetujuan;
* Disetujui;
* Ditolak;
* Selesai;
* Dibatalkan.

## 6.2 Status Absensi

* Belum Absen;
* Valid;
* Di Luar Radius;
* Terlambat;
* Menunggu Sinkronisasi;
* Memerlukan Pemeriksaan.

## 6.3 Status Pelatihan

* Menunggu Persetujuan Atasan;
* Ditolak;
* Menunggu Verifikasi HR;
* Disetujui;
* Sedang Berlangsung;
* Selesai.

## 6.4 Status Mentoring

* Diajukan;
* Dijadwalkan;
* Ditolak;
* Selesai;
* Dibatalkan.

---

# 7. Kebutuhan Nonfungsional

## NFR-01 — Keamanan

Sistem harus menerapkan autentikasi dan hak akses berdasarkan peran.

## NFR-02 — Perlindungan Data

Data lokasi, foto, nilai kinerja, dan kompetensi Pegawai harus dilindungi.

## NFR-03 — Deteksi Lokasi Palsu

Sistem harus memiliki perlindungan dasar terhadap penggunaan mock location.

## NFR-04 — Ketersediaan

Sistem harus dapat diakses selama 24 jam dengan target uptime 99%.

## NFR-05 — Responsif

Sistem harus dapat digunakan melalui komputer dan smartphone.

## NFR-06 — Kemampuan Luring

Absensi harus dapat disimpan sementara ketika tidak ada koneksi internet.

## NFR-07 — Integrasi Google Maps

Sistem harus terhubung dengan Google Maps untuk pencarian lokasi, Map Picker, dan tampilan peta.

## NFR-08 — Keamanan API

Google Maps API Key harus dibatasi berdasarkan domain, jenis API, dan kuota penggunaan.

## NFR-09 — Pencadangan Data

Sistem harus melakukan pencadangan data secara berkala.

---

# 8. Aturan Bisnis

1. Pegawai hanya dapat melakukan absensi pada dinas yang telah disetujui.
2. Pegawai wajib memilih lokasi sebelum mengirim pengajuan.
3. Atasan wajib memeriksa lokasi pada peta.
4. Lokasi tidak dapat diubah setelah pengajuan disetujui.
5. Pegawai wajib mengaktifkan GPS dan kamera saat absensi.
6. Foto absensi harus diambil langsung melalui kamera.
7. Validasi lokasi menggunakan latitude dan longitude.
8. Radius geofencing ditentukan oleh Admin SDM/HR.
9. KPI ditetapkan dan diverifikasi oleh Atasan.
10. Skor merit dipublikasikan setelah diverifikasi.
11. Pengajuan pelatihan harus mendapat persetujuan Atasan.
12. Hasil penilaian 360 derajat ditampilkan dalam bentuk akumulasi.

---

# 9. Data Utama Sistem

Sistem mengelola data:

* Pengguna dan Pegawai;
* Jabatan dan unit kerja;
* Pengajuan dinas;
* Lokasi dinas;
* Latitude dan longitude;
* Radius geofencing;
* Foto dan data absensi;
* KPI dan capaian;
* Penilaian 360 derajat;
* Skor merit;
* Kompetensi dan jalur karier;
* Pelatihan;
* Mentoring;
* Riwayat aktivitas.

---

# 10. Kriteria Keberhasilan

Sistem dianggap berhasil apabila:

1. Pegawai dapat mengajukan dinas.
2. Pegawai dapat memilih lokasi melalui Google Maps.
3. Atasan dapat memeriksa dan menyetujui lokasi dinas.
4. Sistem dapat memvalidasi posisi Pegawai berdasarkan radius.
5. Sistem dapat mengambil foto langsung.
6. Absensi dapat disimpan ketika perangkat luring.
7. Atasan dapat menetapkan dan menilai KPI.
8. Sistem dapat menghitung skor merit.
9. Pegawai dapat melihat jalur karier dan kesenjangan kompetensi.
10. Pegawai dapat mengajukan pelatihan dan mentoring.
11. Admin SDM/HR dapat memantau dan membuat laporan.
