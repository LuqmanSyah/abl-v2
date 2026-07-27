# PRODUCT REQUIREMENTS DOCUMENT

## SERVICE ABSENSI TUGAS LUAR

**Nama Produk:** Sistem SDM
**Nama Layanan:** Service Absensi Tugas Luar
**Versi Dokumen:** 1.0
**Status:** Draft
**Platform:** Aplikasi web mobile-first
**Teknologi Utama:** Laravel, Filament, Google Maps API

---

# 1. Pendahuluan

## 1.1 Latar Belakang

Karyawan yang bekerja di kantor umumnya melakukan absensi menggunakan mesin fingerprint. Namun, fingerprint tidak dapat digunakan ketika karyawan menjalankan kegiatan di luar kantor, seperti meeting dengan klien, kunjungan lapangan, pelatihan, perjalanan dinas, atau pekerjaan di lokasi tertentu.

Perusahaan membutuhkan layanan yang dapat mencatat dan memvalidasi kehadiran karyawan selama menjalankan tugas di luar kantor. Validasi tersebut dilakukan berdasarkan waktu, lokasi GPS, bukti foto, serta tugas luar yang telah diberikan atau disetujui oleh atasan.

Service Absensi Tugas Luar dibuat untuk menangani kebutuhan tersebut tanpa menggantikan sistem fingerprint yang digunakan untuk absensi rutin di kantor.

## 1.2 Tujuan Produk

Service Absensi Tugas Luar bertujuan untuk:

1. Mengelola pengajuan dan penugasan kegiatan di luar kantor.
2. Memastikan absensi GPS hanya dilakukan oleh karyawan yang memiliki tugas luar aktif.
3. Memvalidasi kehadiran berdasarkan lokasi, waktu, dan bukti foto.
4. Mendukung tugas luar satu hari maupun beberapa hari.
5. Membantu atasan memantau dan memverifikasi pelaksanaan tugas luar.
6. Menyediakan laporan absensi tugas luar bagi HR atau Admin.

## 1.3 Definisi Produk

Service Absensi Tugas Luar adalah layanan yang digunakan untuk:

> Mengelola penugasan dan memvalidasi kehadiran karyawan ketika menjalankan meeting, kunjungan, perjalanan dinas, atau kegiatan lain di luar kantor menggunakan waktu, lokasi GPS, dan bukti foto.

Service ini tidak digunakan untuk absensi rutin karyawan yang bekerja di kantor.

---

# 2. Ruang Lingkup

## 2.1 Ruang Lingkup Utama

Service ini mencakup:

* pengajuan tugas luar oleh karyawan;
* pemberian tugas luar oleh atasan;
* persetujuan atau penolakan pengajuan;
* pengaturan jadwal dan lokasi tugas;
* tugas luar satu hari;
* tugas luar beberapa hari;
* lokasi tugas tetap atau berbeda setiap hari;
* check-in menggunakan GPS;
* check-out menggunakan GPS;
* pengambilan foto secara langsung;
* validasi radius lokasi;
* pengajuan alasan ketika berada di luar radius;
* verifikasi absensi oleh atasan;
* pemantauan status tugas;
* riwayat absensi tugas luar;
* laporan pelaksanaan tugas luar.

## 2.2 Di Luar Ruang Lingkup

Service ini tidak menangani:

* absensi rutin di kantor;
* integrasi langsung dengan mesin fingerprint;
* penggajian dan perhitungan lembur;
* penilaian kinerja atau merit;
* pembinaan dan promosi karier;
* penggantian biaya perjalanan dinas;
* pemesanan tiket dan akomodasi;
* pelacakan lokasi karyawan secara terus-menerus;
* pemantauan lokasi di luar proses check-in dan check-out.

---

# 3. Aktor Sistem

## 3.1 Karyawan

Karyawan merupakan pengguna yang menjalankan tugas di luar kantor.

Karyawan dapat:

* mengajukan tugas luar;
* melihat tugas yang diberikan oleh atasan;
* melihat status persetujuan;
* melihat jadwal dan lokasi tugas;
* melakukan check-in;
* melakukan check-out;
* mengambil foto bukti kehadiran;
* memberikan alasan ketika berada di luar radius;
* mengisi catatan pelaksanaan tugas;
* melihat riwayat tugas luar pribadi.

## 3.2 Atasan atau Supervisor

Atasan bertanggung jawab terhadap pelaksanaan tugas luar anggota timnya.

Atasan dapat:

* membuat penugasan untuk karyawan;
* menyetujui atau menolak pengajuan tugas luar;
* mengubah jadwal atau lokasi tugas;
* melihat pelaksanaan tugas anggota tim;
* melihat lokasi dan foto absensi;
* memverifikasi absensi di luar radius;
* melihat tugas yang belum check-in atau check-out;
* melihat laporan tugas luar anggota tim.

## 3.3 HR atau Admin

HR atau Admin bertanggung jawab atas pengelolaan data dan pemantauan sistem secara keseluruhan.

HR atau Admin dapat:

* mengelola data karyawan;
* mengelola data divisi dan jabatan;
* mengelola akun dan hak akses;
* melihat seluruh tugas luar;
* melihat seluruh absensi GPS;
* mengelola aturan radius dan waktu;
* melakukan koreksi data dengan alasan yang tercatat;
* melihat riwayat perubahan data;
* membuat dan mengunduh laporan.

## 3.4 Google Maps Platform

Google Maps Platform merupakan layanan eksternal yang digunakan untuk:

* memilih lokasi tujuan;
* menampilkan titik lokasi pada peta;
* mengubah koordinat menjadi alamat;
* membantu menampilkan posisi check-in dan check-out.

Perhitungan validasi radius tetap dilakukan oleh server aplikasi.

---

# 4. Kebutuhan Fungsional

## 4.1 Autentikasi dan Hak Akses

### FR-01 Login

Sistem harus menyediakan halaman login untuk Karyawan, Atasan, dan HR/Admin.

### FR-02 Hak Akses

Sistem harus membatasi menu dan tindakan berdasarkan peran pengguna.

### FR-03 Hubungan Atasan dan Karyawan

Sistem harus mengetahui atasan langsung dari setiap karyawan agar pengajuan dapat dikirim kepada pihak yang tepat.

---

## 4.2 Pengajuan Tugas Luar oleh Karyawan

### FR-04 Membuat Pengajuan

Karyawan dapat membuat pengajuan tugas luar dengan mengisi:

* judul kegiatan;
* tujuan atau agenda;
* tanggal dan waktu mulai;
* tanggal dan waktu selesai;
* lokasi tujuan;
* radius yang diizinkan;
* keterangan tambahan;
* jadwal lokasi harian jika lokasi berpindah.

### FR-05 Status Awal

Pengajuan yang dibuat oleh karyawan harus memiliki status `pending`.

### FR-06 Persetujuan Atasan

Atasan dapat menyetujui atau menolak pengajuan.

Jika ditolak, atasan wajib memberikan alasan penolakan.

### FR-07 Perubahan Pengajuan

Karyawan dapat mengubah atau membatalkan pengajuan selama pengajuan belum disetujui.

---

## 4.3 Penugasan oleh Atasan

### FR-08 Membuat Penugasan

Atasan dapat membuat tugas luar untuk satu karyawan atau beberapa karyawan.

### FR-09 Persetujuan Penugasan

Tugas yang dibuat langsung oleh atasan dapat berstatus `approved` tanpa memerlukan persetujuan tambahan.

### FR-10 Informasi Penugasan

Penugasan harus memuat:

* karyawan yang ditugaskan;
* judul kegiatan;
* agenda;
* tanggal dan waktu;
* lokasi;
* radius;
* jadwal harian;
* catatan dari atasan.

---

## 4.4 Tugas Luar Satu Hari

### FR-11 Sesi Absensi

Tugas luar satu hari harus memiliki satu sesi absensi yang terdiri atas:

* satu check-in;
* satu check-out.

### FR-12 Waktu Aktif

Tombol check-in hanya tersedia ketika tugas telah disetujui dan sudah memasuki waktu yang diizinkan.

---

## 4.5 Tugas Luar Beberapa Hari

### FR-13 Satu Pengajuan Multi-Hari

Satu pengajuan dapat mencakup tugas luar selama beberapa hari.

### FR-14 Absensi Harian

Untuk tugas beberapa hari, sistem harus menyediakan satu sesi absensi untuk setiap hari kerja.

Contoh:

```text
Tugas Luar 3–5 Agustus

3 Agustus
- Check-in
- Check-out

4 Agustus
- Check-in
- Check-out

5 Agustus
- Check-in
- Check-out
```

### FR-15 Hari Libur

Sistem tidak mewajibkan absensi pada hari yang ditandai sebagai hari libur atau hari tanpa jadwal tugas.

### FR-16 Lokasi Tetap

Jika lokasi tugas sama selama beberapa hari, sistem dapat menggunakan lokasi yang sama untuk seluruh jadwal harian.

### FR-17 Lokasi Berpindah

Jika lokasi tugas berbeda setiap hari, sistem harus menyimpan lokasi target untuk masing-masing tanggal.

Contoh:

| Tanggal   | Lokasi         |
| --------- | -------------- |
| 3 Agustus | Kantor Klien A |
| 4 Agustus | Kantor Klien B |
| 5 Agustus | Gudang C       |

### FR-18 Perubahan Periode

Atasan dapat memperpendek atau memperpanjang periode tugas dengan memberikan alasan perubahan.

---

## 4.6 Check-in Tugas Luar

### FR-19 Tugas Aktif

Karyawan hanya dapat melakukan check-in apabila memiliki tugas luar aktif dan telah disetujui.

### FR-20 Pengambilan GPS

Sistem harus mengambil:

* latitude;
* longitude;
* waktu check-in;
* tingkat akurasi GPS jika tersedia.

### FR-21 Bukti Foto

Karyawan wajib mengambil foto secara langsung ketika melakukan check-in.

Sistem tidak boleh mengizinkan unggahan foto lama dari galeri apabila perangkat mendukung pengambilan foto langsung.

### FR-22 Validasi Radius

Sistem harus menghitung jarak antara posisi karyawan dan lokasi target.

Check-in dianggap sesuai lokasi apabila jarak masih berada dalam radius yang telah ditentukan.

### FR-23 Validasi Waktu

Sistem harus membandingkan waktu check-in dengan jadwal mulai tugas.

Status check-in dapat berupa:

* tepat waktu;
* terlambat;
* di luar waktu yang diizinkan.

### FR-24 Satu Check-in Harian

Karyawan hanya dapat memiliki satu check-in aktif untuk satu jadwal tugas pada tanggal yang sama.

---

## 4.7 Check-out Tugas Luar

### FR-25 Check-out

Karyawan dapat melakukan check-out setelah memiliki check-in yang valid.

### FR-26 Data Check-out

Sistem harus mencatat:

* waktu check-out;
* latitude;
* longitude;
* foto;
* jarak dari lokasi target;
* catatan hasil kegiatan.

### FR-27 Catatan Kegiatan

Sistem dapat mewajibkan karyawan mengisi ringkasan hasil kegiatan sebelum menyelesaikan check-out.

### FR-28 Satu Check-out Harian

Setiap sesi absensi harian hanya dapat memiliki satu check-out.

---

## 4.8 Absensi di Luar Radius

### FR-29 Pengajuan Pengecualian

Jika karyawan berada di luar radius, sistem tidak langsung menolak absensi.

Karyawan harus mengisi:

* alasan berada di luar radius;
* foto langsung;
* catatan pendukung.

### FR-30 Status Verifikasi

Absensi di luar radius harus memiliki status `pending_verification`.

### FR-31 Verifikasi Atasan

Atasan dapat:

* menyetujui absensi;
* menolak absensi;
* meminta penjelasan tambahan.

### FR-32 Alasan Penolakan

Atasan wajib memberikan alasan ketika menolak absensi.

---

## 4.9 Pemantauan Tugas

### FR-33 Daftar Tugas Karyawan

Karyawan dapat melihat:

* tugas mendatang;
* tugas aktif;
* tugas selesai;
* tugas ditolak;
* tugas dibatalkan.

### FR-34 Pemantauan Tim

Atasan dapat melihat:

* karyawan yang sedang bertugas;
* karyawan yang sudah check-in;
* karyawan yang belum check-in;
* karyawan yang belum check-out;
* absensi yang menunggu verifikasi.

### FR-35 Pemantauan Keseluruhan

HR/Admin dapat melihat seluruh tugas dan absensi berdasarkan:

* periode;
* karyawan;
* divisi;
* status;
* lokasi;
* atasan;
* jenis permasalahan.

---

## 4.10 Notifikasi

### FR-36 Notifikasi Pengajuan

Atasan menerima notifikasi ketika terdapat pengajuan tugas luar baru.

### FR-37 Notifikasi Persetujuan

Karyawan menerima notifikasi ketika pengajuannya disetujui atau ditolak.

### FR-38 Pengingat Tugas

Sistem dapat memberikan pengingat sebelum waktu tugas dimulai.

### FR-39 Pengingat Check-out

Sistem dapat memberikan pengingat jika karyawan telah check-in tetapi belum melakukan check-out.

### FR-40 Notifikasi Verifikasi

Atasan menerima notifikasi ketika terdapat absensi di luar radius yang memerlukan verifikasi.

---

## 4.11 Laporan

### FR-41 Laporan Tugas Luar

HR/Admin dapat melihat laporan yang berisi:

* nama karyawan;
* divisi;
* judul tugas;
* lokasi;
* periode tugas;
* waktu check-in;
* waktu check-out;
* status lokasi;
* status keterlambatan;
* status verifikasi;
* catatan kegiatan.

### FR-42 Filter Laporan

Laporan dapat difilter berdasarkan:

* tanggal;
* karyawan;
* divisi;
* atasan;
* lokasi;
* status tugas;
* status absensi.

### FR-43 Ekspor Data

Laporan dapat diekspor dalam format CSV atau Excel.

---

# 5. Aturan Bisnis

## BR-01

Absensi GPS hanya digunakan untuk tugas atau kegiatan di luar kantor.

## BR-02

Absensi rutin di kantor tetap dilakukan melalui fingerprint dan tidak menjadi tanggung jawab service ini.

## BR-03

Setiap absensi GPS wajib terhubung dengan tugas luar yang telah disetujui.

## BR-04

Karyawan tidak dapat melakukan check-in jika tidak memiliki tugas luar aktif.

## BR-05

Pengajuan dari karyawan memerlukan persetujuan atasan.

## BR-06

Penugasan yang dibuat langsung oleh atasan dapat otomatis disetujui.

## BR-07

Satu tugas luar dapat berlangsung selama satu hari atau beberapa hari.

## BR-08

Tugas multi-hari memiliki satu sesi absensi pada setiap hari yang dijadwalkan.

## BR-09

Setiap sesi harian maksimal memiliki satu check-in dan satu check-out.

## BR-10

Hari tanpa jadwal tugas tidak mewajibkan absensi.

## BR-11

Lokasi tugas dapat sama atau berbeda untuk setiap hari.

## BR-12

Absensi di luar radius harus diverifikasi oleh atasan.

## BR-13

Tidak melakukan check-in GPS tidak otomatis membuat karyawan dianggap tidak hadir dari pekerjaan secara keseluruhan.

Service hanya mencatat ketidakhadiran pada kegiatan tugas luar.

## BR-14

Sistem hanya mengambil lokasi pada saat pengguna melakukan check-in atau check-out.

## BR-15

Perubahan data oleh HR/Admin harus menyimpan alasan dan riwayat perubahan.

---

# 6. Status Sistem

## 6.1 Status Tugas Luar

| Status      | Keterangan                   |
| ----------- | ---------------------------- |
| `draft`     | Pengajuan belum dikirim      |
| `pending`   | Menunggu persetujuan         |
| `approved`  | Telah disetujui              |
| `rejected`  | Ditolak                      |
| `ongoing`   | Sedang berlangsung           |
| `completed` | Seluruh jadwal telah selesai |
| `cancelled` | Dibatalkan                   |

## 6.2 Status Absensi Harian

| Status                 | Keterangan                            |
| ---------------------- | ------------------------------------- |
| `not_started`          | Jadwal belum dimulai                  |
| `checked_in`           | Sudah check-in                        |
| `present`              | Check-in dan check-out lengkap        |
| `late`                 | Check-in melewati jadwal              |
| `missing_check_in`     | Tidak melakukan check-in              |
| `missing_check_out`    | Sudah check-in tetapi tidak check-out |
| `pending_verification` | Menunggu verifikasi                   |
| `approved_exception`   | Pengecualian disetujui                |
| `rejected`             | Absensi ditolak                       |

---

# 7. Alur Proses Bisnis

## 7.1 Pengajuan oleh Karyawan

```text
Karyawan membuat pengajuan
        ↓
Pengajuan berstatus pending
        ↓
Atasan memeriksa pengajuan
        ↓
Disetujui atau ditolak
        ↓
Jika disetujui, tugas masuk ke jadwal karyawan
```

## 7.2 Penugasan oleh Atasan

```text
Atasan membuat tugas
        ↓
Memilih karyawan dan jadwal
        ↓
Menentukan lokasi dan radius
        ↓
Tugas langsung berstatus approved
        ↓
Karyawan menerima notifikasi
```

## 7.3 Pelaksanaan Tugas

```text
Tugas telah aktif
        ↓
Karyawan membuka detail tugas
        ↓
Karyawan melakukan check-in
        ↓
Sistem mengambil GPS dan foto
        ↓
Sistem memvalidasi waktu dan radius
        ↓
Karyawan menjalankan kegiatan
        ↓
Karyawan melakukan check-out
        ↓
Sistem menyimpan hasil absensi
```

## 7.4 Tugas Multi-Hari

```text
Satu tugas luar
        ↓
Sistem membuat jadwal per hari
        ↓
Karyawan check-in dan check-out setiap hari
        ↓
Setiap sesi disimpan secara terpisah
        ↓
Seluruh sesi terhubung ke tugas yang sama
        ↓
Tugas selesai setelah seluruh jadwal berakhir
```

## 7.5 Absensi di Luar Radius

```text
Posisi berada di luar radius
        ↓
Karyawan mengisi alasan dan foto
        ↓
Status pending_verification
        ↓
Atasan memeriksa bukti
        ↓
Disetujui atau ditolak
```

---

# 8. Struktur Data Utama

## 8.1 External Duty Requests

Menyimpan data utama tugas luar.

Atribut utama:

* ID tugas;
* karyawan;
* pembuat tugas;
* judul;
* tujuan;
* waktu mulai;
* waktu selesai;
* status;
* pihak yang menyetujui;
* waktu persetujuan;
* alasan penolakan;
* catatan.

## 8.2 External Duty Schedules

Menyimpan jadwal dan lokasi tugas per hari.

Atribut utama:

* ID jadwal;
* ID tugas;
* tanggal;
* waktu mulai;
* waktu selesai;
* nama lokasi;
* alamat;
* latitude;
* longitude;
* radius;
* status jadwal.

## 8.3 External Duty Attendances

Menyimpan absensi harian.

Atribut utama:

* ID absensi;
* ID jadwal;
* ID karyawan;
* waktu check-in;
* koordinat check-in;
* foto check-in;
* jarak check-in;
* waktu check-out;
* koordinat check-out;
* foto check-out;
* jarak check-out;
* catatan kegiatan;
* status;
* alasan pengecualian.

## 8.4 Attendance Verifications

Menyimpan proses verifikasi.

Atribut utama:

* ID verifikasi;
* ID absensi;
* pihak yang memverifikasi;
* keputusan;
* catatan;
* waktu verifikasi.

## 8.5 Audit Logs

Menyimpan riwayat perubahan penting.

Atribut utama:

* pengguna;
* tindakan;
* data yang diubah;
* nilai sebelumnya;
* nilai terbaru;
* waktu perubahan.

---

# 9. Kebutuhan Integrasi

## 9.1 Google Maps API

Digunakan untuk:

* memilih lokasi tujuan;
* menampilkan peta;
* reverse geocoding;
* menampilkan titik check-in dan check-out.

## 9.2 GPS Perangkat

Digunakan untuk mendapatkan koordinat saat pengguna melakukan absensi.

## 9.3 Kamera Perangkat

Digunakan untuk mengambil foto langsung pada saat check-in dan check-out.

## 9.4 Notification Service

Digunakan untuk mengirim notifikasi persetujuan, pengingat, dan verifikasi.

## 9.5 API Service

Service Absensi harus menyediakan API agar data tugas dan hasil absensi dapat digunakan oleh layanan lain sesuai kebutuhan arsitektur berbasis layanan.

Service lain tidak diperbolehkan mengubah tabel internal Service Absensi secara langsung.

---

# 10. Kebutuhan Nonfungsional

## 10.1 Keamanan

* Seluruh halaman harus memerlukan autentikasi.
* Hak akses harus dibatasi berdasarkan peran.
* API harus menggunakan autentikasi.
* Foto dan data lokasi tidak boleh dapat diakses oleh pengguna yang tidak berwenang.
* Perubahan data penting harus tercatat dalam audit log.

## 10.2 Privasi

* Lokasi hanya diambil saat check-in dan check-out.
* Sistem tidak melakukan pelacakan lokasi secara terus-menerus.
* Pengguna harus mengetahui tujuan penggunaan data lokasi dan foto.

## 10.3 Kinerja

* Proses check-in dan check-out sebaiknya selesai dalam waktu maksimal 5 detik pada koneksi normal.
* Daftar tugas harus menggunakan pagination.
* Foto harus dikompresi sebelum disimpan.

## 10.4 Ketersediaan

* Sistem harus dapat digunakan melalui perangkat mobile.
* Antarmuka harus responsif.
* Sistem harus menampilkan pesan yang jelas ketika GPS, kamera, atau koneksi tidak tersedia.

## 10.5 Waktu

Seluruh data waktu menggunakan zona waktu `Asia/Jakarta`.

## 10.6 Akurasi Lokasi

Sistem harus menyimpan tingkat akurasi GPS jika informasi tersebut tersedia.

Absensi dengan akurasi lokasi yang terlalu rendah dapat ditandai untuk diperiksa.

---

# 11. Penanganan Kondisi Khusus

## 11.1 GPS Tidak Aktif

Sistem meminta pengguna mengaktifkan GPS sebelum melakukan absensi.

## 11.2 Izin Lokasi Ditolak

Sistem menampilkan informasi bahwa absensi tidak dapat diproses tanpa izin lokasi.

## 11.3 Kamera Tidak Dapat Digunakan

Sistem menampilkan pesan kesalahan dan meminta pengguna mencoba kembali.

## 11.4 Koneksi Terputus

Sistem menampilkan status kegagalan dan tidak boleh membuat absensi ganda ketika pengguna mengirim ulang.

## 11.5 Lokasi Tidak Akurat

Sistem meminta pengguna memperbarui lokasi atau menunggu hingga akurasi membaik.

## 11.6 Tugas Dibatalkan

Karyawan tidak dapat melakukan check-in terhadap tugas yang telah dibatalkan.

## 11.7 Tugas Selesai Lebih Cepat

Atasan dapat mengubah tanggal akhir tugas dengan mencatat alasan perubahan.

## 11.8 Tugas Diperpanjang

Atasan dapat menambahkan jadwal harian baru ke dalam tugas yang sedang berlangsung.

---

# 12. Kriteria Penerimaan

Service Absensi dinyatakan memenuhi kebutuhan minimum apabila:

1. Karyawan dapat mengajukan tugas luar.
2. Atasan dapat menyetujui atau menolak pengajuan.
3. Atasan dapat membuat penugasan langsung.
4. Karyawan hanya dapat check-in pada tugas yang aktif.
5. Sistem dapat mengambil GPS dan foto saat check-in.
6. Sistem dapat menghitung jarak dari lokasi target.
7. Karyawan dapat melakukan check-out.
8. Tugas multi-hari memiliki absensi terpisah untuk setiap hari.
9. Sistem mendukung lokasi berbeda setiap hari.
10. Absensi di luar radius dapat diverifikasi oleh atasan.
11. HR/Admin dapat melihat seluruh data tugas luar.
12. Laporan dapat difilter dan diekspor.
13. Sistem tidak menyediakan absensi GPS untuk kehadiran rutin di kantor.
14. Sistem tidak melacak lokasi pengguna secara terus-menerus.

---

# 13. Prioritas Pengembangan

## 13.1 MVP

Fitur yang wajib dibuat:

* login dan hak akses;
* data karyawan dan hubungan atasan;
* pengajuan tugas luar;
* penugasan oleh atasan;
* persetujuan dan penolakan;
* jadwal tugas satu hari dan multi-hari;
* lokasi per hari;
* check-in GPS;
* check-out GPS;
* foto;
* validasi radius;
* verifikasi absensi;
* riwayat tugas;
* laporan dasar.

## 13.2 Pengembangan Berikutnya

Fitur lanjutan:

* notifikasi email atau WhatsApp;
* mode PWA;
* pengingat otomatis;
* peta pemantauan tugas aktif;
* tanda tangan digital;
* lampiran dokumen kegiatan;
* laporan yang lebih lengkap;
* deteksi lokasi palsu;
* mode penyimpanan sementara saat koneksi buruk.

---

# 14. Kesimpulan

Service Absensi Tugas Luar berfokus pada pencatatan dan validasi kehadiran karyawan ketika menjalankan kegiatan di luar kantor.

Service ini tidak menggantikan fingerprint. Setiap absensi GPS harus memiliki hubungan dengan tugas luar yang telah disetujui. Untuk tugas yang berlangsung beberapa hari, pengajuan dibuat satu kali, sedangkan check-in dan check-out dilakukan pada setiap hari yang dijadwalkan.

Dengan batas tersebut, tanggung jawab Service Absensi menjadi jelas, terukur, dan sesuai dengan kebutuhan arsitektur berbasis layanan.
