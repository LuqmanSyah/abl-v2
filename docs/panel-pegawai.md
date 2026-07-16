# Panduan Panel Pegawai

## 1. Gambaran Umum

Panel Pegawai merupakan portal untuk pegawai aktif. Panel tersedia pada alamat `/pegawai` setelah pengguna masuk dengan akun berperan **Pegawai**.

Panel memiliki tiga kelompok menu: **Operasional**, **Kinerja**, dan **Pengembangan**. Data yang ditampilkan dibatasi sesuai akun pegawai yang sedang masuk.

## 2. Dasbor

Dasbor menampilkan ringkasan aktivitas pegawai dalam enam kartu statistik:

- **Dinas aktif**: jumlah dinas berstatus disetujui. Kartu membuka menu Dinas Saya.
- **Dinas selesai**: jumlah dinas yang telah selesai. Kartu membuka riwayat Dinas Saya.
- **Riwayat absensi**: jumlah seluruh absensi milik pegawai. Kartu membuka Riwayat Absensi.
- **Hasil merit terbit**: jumlah hasil merit yang telah diterbitkan HR. Kartu membuka Hasil Merit.
- **Pelatihan disetujui**: jumlah pengajuan pelatihan berstatus disetujui. Kartu membuka Pengajuan Pelatihan.
- **Mentoring terjadwal**: jumlah mentoring berstatus disetujui. Kartu membuka Mentoring.

Dasbor juga memuat kartu akun untuk melihat identitas pengguna yang sedang masuk.

## 3. Kelompok Operasional

### 3.1 Dinas Saya

Menu ini menampilkan seluruh tugas dinas milik pegawai. Tugas dinas dibuat oleh atasan langsung; pegawai tidak dapat membuat, mengubah, atau menghapusnya dari panel.

Daftar dinas berisi:

- pegawai dan atasan pemberi tugas;
- tujuan dinas;
- waktu mulai dan selesai;
- lokasi;
- status dinas;
- waktu pembuatan dan pembaruan sebagai kolom opsional.

Daftar dapat dicari berdasarkan pegawai, atasan, tujuan, lokasi, atau status. Filter **Status dinas** dapat dipakai untuk membatasi hasil. Urutan awal menampilkan waktu mulai terbaru.

Aksi **Lihat** membuka detail berikut:

- pegawai dan atasan;
- lokasi terdaftar, tujuan, dan keperluan dinas;
- waktu mulai dan selesai;
- nama lokasi dan alamat lengkap;
- titik lokasi pada peta, garis lintang, garis bujur, dan radius absensi;
- dokumen pendukung bila tersedia;
- status dan waktu penugasan;
- waktu pembuatan dan pembaruan data.

### 3.2 Riwayat Absensi

Menu ini menampilkan bukti absensi milik pegawai. Data dibuat melalui proses absensi dinas dan bersifat hanya-baca pada panel: tidak dapat ditambah, diubah, atau dihapus.

Daftar absensi berisi:

- dinas terkait dan nama pegawai;
- waktu absensi;
- koordinat lokasi;
- akurasi GPS dan jarak dari lokasi dinas;
- status absensi;
- penanda GPS mencurigakan;
- waktu sinkronisasi serta waktu pembuatan dan pembaruan sebagai kolom opsional.

Filter **Status absensi** tersedia. Urutan awal menampilkan absensi terbaru.

Aksi **Lihat** membuka detail lengkap: ID sinkronisasi, dinas, pegawai, waktu absensi, koordinat, akurasi GPS, jarak, foto bukti, status, indikasi lokasi yang perlu diperiksa, serta waktu sinkronisasi.

## 4. Kelompok Kinerja

### 4.1 KPI Pegawai

Menu ini menampilkan KPI milik pegawai yang ditetapkan atasan. Pegawai hanya dapat melihat data; pembuatan, perubahan, dan penghapusan KPI menjadi kewenangan atasan.

Daftar KPI berisi:

- periode penilaian;
- indikator KPI;
- pegawai;
- atasan sebagai kolom opsional;
- target;
- capaian;
- waktu pembuatan dan pembaruan sebagai kolom opsional.

Daftar dapat dicari berdasarkan periode, indikator, pegawai, atau atasan. Aksi **Lihat** menampilkan periode, indikator, pegawai, atasan, target, capaian, dan catatan.

### 4.2 Penilaian 360

Menu ini dipakai pegawai untuk memberi penilaian kepada atasan langsung atau rekan satu unit. Pegawai tidak dapat menilai dirinya sendiri.

Daftar hanya menampilkan penilaian yang pernah dikirim oleh pegawai tersebut, meliputi:

- periode;
- penilai;
- pegawai yang dinilai;
- jenis penilaian: pegawai ke atasan atau rekan sejawat;
- nilai;
- waktu pengiriman.

Tombol **Buat Penilaian 360** membuka formulir dengan isian:

- periode penilaian yang masih aktif;
- pegawai yang dinilai;
- nilai 1 sampai 5;
- komentar opsional.

Sistem mengisi identitas penilai, jenis penilaian, dan waktu pengiriman secara otomatis. Penilaian yang telah dikirim hanya dapat dilihat dan tidak dapat diubah atau dihapus.

### 4.3 Hasil Merit

Menu ini menampilkan hasil merit milik pegawai yang sudah diverifikasi atasan, diverifikasi HR, dan diterbitkan. Hasil yang belum diterbitkan tidak terlihat oleh pegawai.

Daftar dan halaman detail memuat:

- periode penilaian;
- nilai KPI;
- nilai kedisiplinan;
- nilai atasan;
- nilai penilaian 360;
- total skor merit;
- estimasi bonus;
- informasi waktu verifikasi dan penerbitan.

Data hasil merit bersifat hanya-baca. Pegawai tidak dapat membuat, mengubah, atau menghapusnya.

## 5. Kelompok Pengembangan

### 5.1 Kompetensi Pegawai

Menu ini menampilkan hasil asesmen kompetensi milik pegawai:

- nama kompetensi;
- level saat ini, dari 1 sampai 5;
- tanggal penilaian;
- catatan asesor.

Data dikelola HR. Pegawai hanya dapat melihat dan mencari riwayat kompetensinya.

### 5.2 Target Karier

Menu ini dipakai untuk menentukan satu jabatan tujuan. Jabatan yang dapat dipilih harus memiliki level lebih tinggi daripada jabatan pegawai saat ini.

Daftar menampilkan:

- pegawai;
- jabatan saat ini;
- jabatan tujuan;
- ringkasan kesenjangan kompetensi dan rekomendasi pengembangan.

Pegawai dapat membuat target bila belum memilikinya, kemudian mengubah atau menghapus target miliknya. Setelah satu target tersimpan, tombol pembuatan target baru tidak tersedia sampai target lama dihapus.

### 5.3 Katalog Pelatihan

Menu ini menampilkan pelatihan aktif yang disediakan HR. Daftar berisi:

- nama pelatihan;
- kompetensi terkait;
- penyelenggara;
- jenis internal atau eksternal;
- waktu mulai;
- status aktif.

Pegawai hanya dapat melihat dan mencari katalog. Pembuatan dan perubahan data pelatihan dilakukan HR.

### 5.4 Pengajuan Pelatihan

Menu ini dipakai untuk mengajukan pelatihan kepada atasan langsung. Pengajuan baru tersedia bila akun pegawai memiliki atasan langsung.

Formulir pengajuan berisi:

- pelatihan aktif yang belum pernah diajukan pegawai;
- alasan pengajuan.

Daftar pengajuan menampilkan pegawai, pelatihan, status, alasan, catatan atasan, hasil pelatihan, dan waktu pengajuan. Alur status:

1. Pengajuan dikirim kepada atasan.
2. Persetujuan atasan meneruskan pengajuan kepada HR.
3. Verifikasi HR mengubah status menjadi disetujui.
4. HR mencatat hasil setelah pelatihan selesai.

Pengajuan tidak dapat diubah atau dihapus. Bila ditolak, aksi **Ajukan Ulang** tersedia untuk memperbarui alasan dan mengirim kembali pengajuan kepada atasan. Pelatihan yang pernah diajukan dikelola melalui baris pengajuan lama, bukan melalui pengajuan baru.

### 5.5 Mentoring

Menu ini dipakai untuk mengajukan sesi mentoring kepada atasan langsung. Fitur pembuatan tersedia bila akun pegawai memiliki atasan langsung.

Formulir pengajuan berisi:

- topik mentoring;
- target yang ingin dicapai;
- jadwal yang diajukan, minimal waktu saat ini.

Daftar mentoring menampilkan pegawai, topik, target, status, jadwal yang diajukan, jadwal yang disetujui, hasil diskusi, dan tindak lanjut.

Setelah dikirim, pengajuan tidak dapat diubah atau dihapus. Atasan dapat menjadwalkan atau menolak permintaan. Setelah sesi terlaksana, atasan mencatat hasil diskusi dan tindak lanjut. Pegawai memantau seluruh perkembangan dari baris mentoring yang sama.

## 6. Navigasi dan Akun

Sidebar dapat diciutkan pada tampilan desktop. Peringatan perubahan belum disimpan muncul bila pengguna meninggalkan formulir yang masih berubah.

Menu akun dipakai untuk melihat akun yang sedang aktif dan keluar dari aplikasi. Hanya akun aktif dengan peran Pegawai yang dapat membuka Panel Pegawai.

## 7. Ringkasan Hak Akses Pegawai

- **Hanya lihat:** Dinas Saya, Riwayat Absensi, KPI Pegawai, Hasil Merit, Kompetensi Pegawai, dan Katalog Pelatihan.
- **Buat dan lihat:** Penilaian 360.
- **Buat, ubah, dan hapus data sendiri:** Target Karier.
- **Buat dan pantau proses:** Pengajuan Pelatihan dan Mentoring.
- **Data personal:** panel membatasi data operasional, KPI, hasil merit, kompetensi, target karier, pengajuan pelatihan, dan mentoring sesuai akun pegawai yang masuk.
