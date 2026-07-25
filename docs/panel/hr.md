# Panduan Panel HR

## 1. Gambaran Umum

Panel HR merupakan portal administrasi untuk pengguna aktif dengan peran **Admin SDM/HR**. Panel tersedia pada alamat `/hr` setelah pengguna masuk.

Sidebar memiliki lima kelompok menu: **Organisasi**, **Operasional**, **Kinerja**, **Pengembangan**, dan **Laporan & Audit**. HR dapat melihat data seluruh pegawai, atasan, unit, serta proses SDM yang tersimpan di sistem. Hak perubahan berbeda pada setiap menu dan dijelaskan pada bagian terkait.

## 2. Dasbor

Dasbor menampilkan enam kartu statistik:

- **Pegawai aktif**: jumlah pengguna aktif dengan peran Pegawai. Kartu membuka menu Pegawai.
- **Dinas aktif**: jumlah dinas berstatus disetujui. Kartu membuka Monitoring Dinas.
- **Absensi hari ini**: jumlah absensi yang tercatat pada hari berjalan. Kartu membuka Riwayat Absensi.
- **Merit perlu verifikasi HR**: jumlah hasil merit yang sudah diverifikasi atasan tetapi belum diverifikasi HR. Kartu membuka Hasil Merit.
- **Pelatihan perlu verifikasi**: jumlah pengajuan dari Pegawai yang berstatus Menunggu HR. Rekomendasi Atasan yang langsung Disetujui tidak masuk hitungan. Kartu membuka Pengajuan Pelatihan.
- **Mentoring aktif**: jumlah mentoring berstatus dijadwalkan. Kartu membuka Mentoring.

Dasbor juga memuat kartu akun untuk melihat identitas pengguna yang sedang masuk.

## 3. Kelompok Organisasi

### 3.1 Pegawai

Menu ini mengelola seluruh akun Pegawai, Atasan, dan Admin SDM/HR.

Daftar pengguna berisi:

- nama dan email;
- peran;
- unit kerja dan jabatan;
- atasan langsung;
- NIP atau nomor pegawai;
- nomor telepon;
- status aktif;
- waktu pembuatan dan pembaruan sebagai kolom opsional.

Nama, email, peran, unit, jabatan, atasan, nomor pegawai, dan telepon dapat dicari. Setiap baris menyediakan aksi **Ubah**.

Tombol **Buat Pegawai** membuka formulir:

- nama dan email wajib;
- kata sandi wajib saat membuat akun dan opsional saat mengubah akun;
- peran: Pegawai, Atasan, atau Admin SDM/HR;
- unit kerja;
- jabatan, yang baru dapat dipilih setelah unit kerja dipilih dan dibatasi pada jabatan dalam unit tersebut;
- atasan langsung, hanya tampil untuk peran Pegawai dan hanya memuat akun Atasan aktif;
- NIP atau nomor pegawai;
- telepon;
- status aktif.

Email dan nomor pegawai harus unik. Pengguna tidak dapat dihapus. Atasan yang masih memiliki bawahan tidak dapat dinonaktifkan atau diubah ke peran lain.

### 3.2 Unit Kerja

Menu ini mengelola struktur unit organisasi. Daftar memuat nama, kode, serta waktu pembuatan dan pembaruan sebagai kolom opsional. Nama dan kode dapat dicari.

Formulir tambah atau ubah berisi:

- **Nama** unit;
- **Kode** unik unit.

HR dapat membuat, mengubah, menghapus satu unit, atau menghapus beberapa unit melalui aksi massal. Penghapusan tetap mengikuti batas relasi data pada basis data.

### 3.3 Jabatan

Menu ini mengelola jabatan dalam setiap unit kerja. Daftar memuat unit kerja, nama jabatan, level, serta waktu pembuatan dan pembaruan sebagai kolom opsional. Unit dan jabatan dapat dicari; level dapat diurutkan.

Formulir tambah atau ubah berisi:

- unit kerja;
- nama jabatan;
- level numerik minimal 1.

HR dapat membuat, mengubah, menghapus satu jabatan, atau menghapus beberapa jabatan melalui aksi massal.

## 4. Kelompok Operasional

### 4.1 Monitoring Dinas

Menu ini menampilkan seluruh perintah dinas yang dibuat atasan untuk pegawai. HR memakai menu ini untuk pemantauan; HR tidak dapat membuat, mengubah, membatalkan, atau menghapus dinas.

Daftar dinas berisi:

- pegawai dan atasan;
- tujuan dinas;
- waktu mulai dan selesai;
- lokasi;
- status dinas;
- waktu pembuatan dan pembaruan sebagai kolom opsional.

Data dapat dicari berdasarkan pegawai, atasan, tujuan, lokasi, atau status. Filter **Status dinas** tersedia. Urutan awal menampilkan waktu mulai terbaru.

Aksi **Lihat** membuka detail lengkap: pegawai, atasan, lokasi terdaftar, tujuan, keperluan, jadwal, nama dan alamat lokasi, titik pada peta, koordinat, radius absensi, dokumen pendukung, status, waktu penugasan, serta waktu pencatatan.

### 4.2 Riwayat Absensi

Menu ini menampilkan seluruh absensi pegawai dan bersifat hanya-baca.

Daftar absensi berisi:

- dinas terkait dan pegawai;
- waktu absensi;
- koordinat sebagai kolom opsional;
- akurasi GPS dan jarak dari lokasi dinas;
- status absensi;
- penanda GPS mencurigakan;
- waktu sinkronisasi, pembuatan, dan pembaruan sebagai kolom opsional.

Filter **Status absensi** menyediakan status Valid, Di Luar Radius, Terlambat, Menunggu Sinkronisasi, dan Memerlukan Pemeriksaan. Urutan awal menampilkan absensi terbaru.

Aksi **Lihat** membuka ID sinkronisasi, dinas, pegawai, waktu dan koordinat absensi, akurasi GPS, jarak, foto bukti, status, indikasi lokasi yang perlu diperiksa, serta waktu sinkronisasi. HR dapat membuka foto bukti, tetapi tidak dapat menambah, mengubah, atau menghapus absensi.

### 4.3 Lokasi Dinas

Menu ini mengelola lokasi standar yang dapat dipilih atasan saat membuat perintah dinas.

Daftar memuat nama lokasi, koordinat, radius geofence, status aktif, serta waktu pembuatan dan pembaruan sebagai kolom opsional. Nama lokasi dapat dicari; koordinat dan radius dapat diurutkan.

Formulir tambah atau ubah berisi:

- nama lokasi dan alamat;
- pencarian atau pemilihan titik pada peta;
- latitude antara -90 dan 90;
- longitude antara -180 dan 180;
- radius geofence minimal 10 meter, dengan nilai awal 100 meter;
- status aktif.

HR dapat membuat, mengubah, menghapus satu lokasi, atau menghapus beberapa lokasi melalui aksi massal.

## 5. Kelompok Kinerja

### 5.1 Periode Penilaian

Menu ini mengelola periode dan komposisi perhitungan merit.

Daftar periode berisi:

- nama periode;
- tanggal mulai dan selesai;
- bobot KPI, kedisiplinan, penilaian atasan, dan penilaian 360;
- dasar estimasi bonus;
- status aktif;
- waktu pembuatan dan pembaruan sebagai kolom opsional.

Formulir tambah atau ubah berisi nama periode, rentang tanggal, empat bobot bernilai 0 sampai 100 persen, dasar estimasi bonus, dan status aktif. Tanggal selesai tidak boleh sebelum tanggal mulai. Nilai awal bobot adalah KPI 40%, kedisiplinan 20%, atasan 20%, dan penilaian 360 20%.

Aksi **Hitung Merit** meminta konfirmasi lalu menghitung atau memperbarui hasil merit bagi seluruh pegawai yang memiliki KPI pada periode tersebut. Aksi **Ubah** tersedia selama belum ada hasil merit yang dipublikasikan. Periode tidak dapat dihapus.

### 5.2 Indikator KPI

Menu ini mengelola indikator KPI dalam periode penilaian.

Daftar memuat periode, nama indikator, satuan, bobot, serta waktu pembuatan dan pembaruan sebagai kolom opsional. Periode dan nama indikator dapat dicari; bobot dapat diurutkan.

Formulir tambah atau ubah berisi:

- periode yang belum memiliki hasil merit terbit;
- nama indikator;
- deskripsi;
- satuan;
- bobot 1 sampai 100 persen.

HR dapat membuat, mengubah, dan menghapus indikator selama hasil merit pada periodenya belum dipublikasikan. Penghapusan beberapa indikator tersedia melalui aksi massal dengan batas yang sama.

### 5.3 KPI Pegawai

Menu ini menampilkan seluruh KPI yang ditetapkan atasan kepada pegawai.

Daftar dan detail memuat periode, indikator, pegawai, atasan, target, capaian, catatan, serta waktu pencatatan. Data dapat dicari melalui periode, indikator, pegawai, atau atasan.

HR hanya memantau dan melihat detail. Pembuatan, perubahan, dan penghapusan KPI dilakukan atasan; KPI terkunci setelah hasil merit terkait dipublikasikan.

### 5.4 Penilaian 360

Menu ini menampilkan seluruh penilaian 360 yang telah dikirim oleh pegawai atau atasan.

Daftar berisi:

- periode;
- penilai;
- pegawai yang dinilai;
- jenis penilaian;
- nilai dari 1 sampai 5;
- waktu pengiriman;
- waktu pembuatan dan pembaruan sebagai kolom opsional.

Aksi **Lihat** menambahkan komentar penilai pada detail. Urutan awal menampilkan pengiriman terbaru. HR tidak dapat membuat, mengubah, atau menghapus penilaian.

### 5.5 Hasil Merit

Menu ini menampilkan hasil perhitungan merit seluruh pegawai.

Daftar dan detail memuat:

- periode dan pegawai;
- nilai KPI, kedisiplinan, atasan, dan penilaian 360;
- total skor merit;
- estimasi bonus;
- ID dan waktu verifikasi atasan;
- ID dan waktu verifikasi HR;
- waktu publikasi dan waktu pencatatan.

Setelah atasan memverifikasi hasil, halaman detail menampilkan aksi **Verifikasi dan Publikasikan** kepada HR. Aksi meminta konfirmasi, mencatat verifikasi HR, dan mempublikasikan hasil agar dapat dilihat pegawai. Hasil merit tidak dapat dibuat, diubah, atau dihapus secara manual.

## 6. Kelompok Pengembangan

### 6.1 Kompetensi

Menu ini mengelola master kompetensi. Daftar memuat nama kompetensi dan deskripsi. Nama dapat dicari dan diurutkan; deskripsi panjang dipersingkat pada tabel.

Formulir berisi nama unik dan deskripsi. HR dapat membuat, mengubah, dan menghapus kompetensi.

### 6.2 Standar Kompetensi Jabatan

Menu ini menetapkan level kompetensi yang wajib untuk suatu jabatan.

Daftar memuat jabatan, kompetensi, dan level wajib. Jabatan serta kompetensi dapat dicari; jabatan dan level dapat diurutkan.

Formulir berisi jabatan, kompetensi, dan level wajib dari 1 sampai 5. HR dapat membuat, mengubah, dan menghapus standar.

### 6.3 Kompetensi Pegawai

Menu ini mencatat hasil asesmen kompetensi pegawai.

Daftar memuat pegawai, kompetensi, level, tanggal penilaian, dan catatan. Pegawai serta kompetensi dapat dicari; level dan tanggal dapat diurutkan.

Formulir berisi:

- pengguna dengan peran Pegawai;
- kompetensi;
- level saat ini dari 1 sampai 5;
- tanggal penilaian, dengan nilai awal tanggal hari ini;
- catatan asesor.

HR dapat membuat, mengubah, dan menghapus hasil asesmen.

### 6.4 Target Karier

Menu ini menampilkan sasaran karier seluruh pegawai:

- pegawai;
- jabatan saat ini;
- jabatan tujuan;
- ringkasan kesenjangan kompetensi dan rekomendasi pengembangan.

Pegawai membuat dan mengelola targetnya sendiri. HR hanya memantau data dan tidak dapat membuat, mengubah, atau menghapus target.

### 6.5 Katalog Pelatihan

Menu ini mengelola seluruh pelatihan, termasuk yang tidak aktif.

Daftar berisi nama pelatihan, kompetensi terkait, penyelenggara, jenis internal atau eksternal, waktu mulai, dan status aktif.

Formulir tambah atau ubah berisi:

- nama pelatihan;
- penyelenggara;
- jenis internal atau eksternal;
- kompetensi terkait;
- waktu mulai dan selesai;
- status aktif;
- deskripsi.

Waktu selesai harus setelah waktu mulai. HR dapat membuat dan mengubah pelatihan, tetapi tidak dapat menghapusnya. Pengguna non-HR hanya melihat pelatihan aktif.

### 6.6 Pengajuan Pelatihan

Menu ini memantau seluruh pengajuan Pegawai dan rekomendasi Atasan. Daftar berisi pegawai, pelatihan, status, alasan, catatan atasan, hasil pelatihan, dan waktu pengajuan.

Alur pengajuan:

1. Pegawai mengirim pengajuan kepada atasan.
2. Atasan menyetujui atau menolak pengajuan.
3. Pengajuan yang disetujui atasan berstatus **Menunggu HR**.
4. Aksi **Verifikasi HR** meminta konfirmasi lalu mengubah status menjadi **Disetujui**.
5. Setelah pelatihan berjalan, aksi **Catat Hasil** mewajibkan isi hasil pelatihan lalu mengubah status menjadi **Selesai**.

Alur rekomendasi Atasan:

1. Atasan memilih hasil merit bawahan, pelatihan aktif, dan mengisi alasan.
2. Rekomendasi langsung berstatus **Disetujui** tanpa aksi **Verifikasi HR** dan tanpa menambah antrean Pelatihan perlu verifikasi.
3. HR memantau rekomendasi dan memakai aksi **Catat Hasil** setelah pelatihan selesai.

HR tidak dapat membuat, mengubah, atau menghapus pengajuan secara langsung. Tindakan HR hanya tersedia sesuai status baris.

### 6.7 Mentoring

Menu ini memantau seluruh permintaan mentoring antara pegawai dan atasan.

Daftar berisi pegawai, topik, target, status, jadwal yang diajukan, jadwal yang disetujui, hasil diskusi, dan tindak lanjut.

Pegawai membuat permintaan. Atasan menjadwalkan atau menolak, lalu mencatat hasil dan tindak lanjut saat mentoring selesai. HR hanya memantau; tidak dapat membuat, mengubah, menghapus, menjadwalkan, menolak, atau menyelesaikan mentoring.

## 7. Kelompok Laporan & Audit

### 7.1 Laporan SDM

Menu ini membuka halaman ringkasan SDM per pegawai. Tersedia tiga filter:

- **Periode**;
- **Unit**;
- **Jabatan**.

Filter periode membatasi absensi berdasarkan waktu absensi, pelatihan dan mentoring berdasarkan waktu pengajuan, serta memilih hasil merit terbaru dalam periode. Filter unit dan jabatan membatasi pegawai yang ditampilkan.

Tabel laporan berisi:

- NIP, nama pegawai, unit, dan jabatan;
- total absensi dan jumlah absensi valid;
- skor merit;
- total pelatihan dan pelatihan selesai;
- total mentoring dan mentoring selesai.

Tombol **Terapkan** menjalankan filter. **Hapus filter** mengembalikan seluruh data. **Unduh CSV** mengekspor hasil yang sedang difilter dengan nama berformat `laporan-sdm-YYYYMMDD-HHMMSS.csv`. Tautan **Kembali ke panel HR** membuka dasbor HR.

### 7.2 Riwayat Aktivitas

Menu ini menampilkan jejak aktivitas sistem dengan urutan terbaru:

- waktu aktivitas;
- pengguna, atau **Sistem** bila tidak terkait pengguna;
- aksi;
- jenis data;
- ID data.

Pengguna dan aksi dapat dicari. Data audit bersifat hanya-baca; HR tidak dapat menambah, mengubah, atau menghapusnya.

## 8. Navigasi dan Akun

Sidebar dapat diciutkan pada tampilan desktop. Peringatan perubahan belum disimpan muncul bila HR meninggalkan formulir yang masih berubah. Operasi penyimpanan dijalankan dalam transaksi basis data.

Menu akun dipakai untuk melihat akun aktif dan keluar dari aplikasi. Hanya akun aktif dengan peran Admin SDM/HR yang dapat membuka Panel HR dan halaman Laporan SDM.

## 9. Ringkasan Hak Akses HR

- **Kelola penuh:** Pegawai tanpa penghapusan akun, Unit Kerja, Jabatan, Lokasi Dinas, Kompetensi, Standar Kompetensi Jabatan, dan Kompetensi Pegawai.
- **Kelola dengan batas proses:** Periode Penilaian, Indikator KPI, Katalog Pelatihan, dan Hasil Merit.
- **Proses sesuai status:** verifikasi Pengajuan Pelatihan dari Pegawai, pantau rekomendasi Atasan, catat hasil kedua jalur pelatihan, serta verifikasi dan publikasi Hasil Merit.
- **Hanya lihat atau monitor:** Monitoring Dinas, Riwayat Absensi, KPI Pegawai, Penilaian 360, Target Karier, Mentoring, dan Riwayat Aktivitas.
- **Laporan:** filter ringkasan SDM dan ekspor hasil ke CSV.
