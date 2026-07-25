# Panduan Panel Atasan

## 1. Gambaran Umum

Panel Atasan merupakan portal untuk pengguna aktif dengan peran **Atasan**. Panel tersedia pada alamat `/atasan` setelah pengguna masuk.

Panel memiliki tiga kelompok menu: **Operasional**, **Kinerja**, dan **Pengembangan**. Data dibatasi pada pegawai yang menjadi bawahan langsung atasan yang sedang masuk.

## 2. Dasbor

Dasbor menampilkan enam kartu statistik:

- **Dinas aktif**: jumlah dinas berstatus disetujui yang dibuat atasan. Kartu membuka Perintah Dinas.
- **Total tugas bawahan**: jumlah seluruh perintah dinas milik bawahan. Kartu membuka Perintah Dinas.
- **Absensi bawahan**: jumlah absensi dari dinas yang dibuat atasan. Kartu membuka Riwayat Absensi.
- **Merit perlu verifikasi**: jumlah hasil merit bawahan yang belum diverifikasi atasan. Kartu membuka Hasil Merit.
- **Pelatihan perlu persetujuan**: jumlah pengajuan pelatihan yang menunggu keputusan atasan. Kartu membuka Pengajuan Pelatihan.
- **Mentoring perlu jadwal**: jumlah permintaan mentoring yang belum dijadwalkan. Kartu membuka Mentoring.

Dasbor juga memuat kartu akun untuk melihat identitas pengguna yang sedang masuk.

## 3. Kelompok Operasional

### 3.1 Perintah Dinas

Menu ini dipakai untuk membuat dan memantau tugas dinas bagi bawahan langsung.

Daftar dinas berisi:

- pegawai yang ditugaskan;
- atasan pemberi tugas;
- tujuan dinas;
- waktu mulai dan selesai;
- lokasi;
- status dinas;
- waktu pembuatan dan pembaruan sebagai kolom opsional.

Daftar dapat dicari berdasarkan pegawai, atasan, tujuan, lokasi, atau status. Filter **Status dinas** dapat dipakai untuk membatasi hasil. Urutan awal menampilkan waktu mulai terbaru.

Tombol **Buat Dinas** membuka formulir dengan tiga bagian:

1. **Penugasan**: bawahan yang ditugaskan, tujuan, keperluan, waktu mulai, dan waktu selesai.
2. **Lokasi absensi**: lokasi tersimpan atau titik baru pada peta, nama lokasi, alamat, koordinat, serta batas jarak absensi. Lokasi tersimpan mengisi detail lokasi secara otomatis.
3. **Lampiran**: dokumen pendukung opsional berformat PDF, JPG, atau PNG dengan ukuran maksimal 5 MB.

Sistem otomatis mencatat atasan, menetapkan status disetujui, dan menyimpan waktu penugasan.

Aksi **Lihat** membuka detail penugasan, lokasi pada peta, radius absensi, lampiran, status, serta waktu pencatatan. Aksi **Ubah** dan **Batalkan Tugas** hanya tersedia bila:

- tugas dibuat oleh atasan yang sedang masuk;
- status masih disetujui;
- waktu mulai masih akan datang; dan
- pegawai belum melakukan absensi.

Perintah dinas tidak dapat dihapus. Lokasi dinas yang telah memiliki absensi atau telah selesai juga tidak dapat diubah.

### 3.2 Riwayat Absensi

Menu ini menampilkan absensi bawahan dari perintah dinas yang dibuat atasan. Data bersifat hanya-baca.

Daftar absensi berisi:

- dinas terkait dan nama pegawai;
- waktu absensi;
- koordinat lokasi;
- akurasi GPS dan jarak dari lokasi dinas;
- status absensi;
- penanda GPS mencurigakan;
- waktu sinkronisasi serta waktu pembuatan dan pembaruan sebagai kolom opsional.

Filter **Status absensi** tersedia. Urutan awal menampilkan absensi terbaru.

Aksi **Lihat** membuka ID sinkronisasi, dinas, pegawai, waktu absensi, koordinat, akurasi GPS, jarak, foto bukti, status, indikasi lokasi yang perlu diperiksa, serta waktu sinkronisasi. Atasan tidak dapat menambah, mengubah, atau menghapus data absensi.

## 4. Kelompok Kinerja

### 4.1 KPI Pegawai

Menu ini dipakai untuk menetapkan dan memperbarui KPI bawahan langsung.

Daftar KPI berisi:

- periode penilaian;
- indikator KPI;
- pegawai;
- atasan sebagai kolom opsional;
- target;
- capaian;
- waktu pembuatan dan pembaruan sebagai kolom opsional.

Tombol **Buat KPI Pegawai** membuka formulir:

- periode yang belum memiliki hasil merit terbit;
- indikator KPI dari periode terpilih;
- bawahan langsung;
- target lebih dari nol;
- capaian, minimal nol;
- catatan opsional.

Atasan dapat melihat, mengubah, dan menghapus KPI yang dibuatnya selama hasil merit untuk pegawai dan periode tersebut belum diterbitkan. Setelah hasil merit terbit, KPI terkunci.

### 4.2 Penilaian 360

Menu ini dipakai atasan untuk memberi penilaian kepada bawahan langsung.

Daftar hanya menampilkan penilaian yang dikirim oleh atasan tersebut, meliputi:

- periode;
- penilai;
- pegawai yang dinilai;
- jenis penilaian;
- nilai;
- waktu pengiriman.

Tombol **Buat Penilaian 360** membuka formulir dengan isian:

- periode penilaian yang masih aktif;
- bawahan langsung yang dinilai;
- nilai 1 sampai 5;
- komentar opsional.

Sistem mengisi identitas penilai, jenis **Atasan ke Pegawai**, dan waktu pengiriman secara otomatis. Penilaian yang telah dikirim hanya dapat dilihat; tidak dapat diubah atau dihapus.

### 4.3 Hasil Merit

Menu ini menampilkan hasil merit bawahan langsung, termasuk hasil yang belum diterbitkan. Daftar dan detail memuat:

- periode dan pegawai;
- nilai KPI;
- nilai kedisiplinan;
- nilai atasan;
- nilai penilaian 360;
- total skor merit;
- estimasi bonus;
- informasi verifikasi atasan, verifikasi HR, dan penerbitan.

Setiap hasil merit bawahan menyediakan aksi **Rekomendasikan Pelatihan**. Modal menampilkan periode, total dan komponen skor, detail KPI beserta riwayat perubahannya, detail penilaian, serta disiplin. Atasan memilih pelatihan aktif dan mengisi alasan. Rekomendasi langsung berstatus **Disetujui** dan tidak masuk antrean verifikasi HR.

Pada hasil yang belum diverifikasi atasan, halaman detail menyediakan aksi **Verifikasi Atasan** dengan konfirmasi. Setelah verifikasi:

1. data menunggu verifikasi HR;
2. HR memverifikasi dan menerbitkan hasil; dan
3. hasil terbit dapat dilihat pegawai.

Hasil merit tidak dapat dibuat, diubah, atau dihapus dari panel atasan.

## 5. Kelompok Pengembangan

### 5.1 Kompetensi Pegawai

Menu ini menampilkan hasil asesmen kompetensi bawahan langsung:

- nama pegawai;
- kompetensi;
- level saat ini, dari 1 sampai 5;
- tanggal penilaian;
- catatan asesor.

Data dikelola HR. Atasan hanya dapat melihat dan mencari data kompetensi bawahan.

### 5.2 Target Karier

Menu ini menampilkan sasaran karier bawahan langsung:

- pegawai;
- jabatan saat ini;
- jabatan tujuan;
- ringkasan kesenjangan kompetensi dan rekomendasi pengembangan.

Target dibuat dan dikelola oleh pegawai. Atasan memakai informasi ini untuk memahami kebutuhan pengembangan bawahan, tanpa hak membuat, mengubah, atau menghapus target.

### 5.3 Katalog Pelatihan

Menu ini menampilkan pelatihan aktif yang disediakan HR:

- nama pelatihan;
- kompetensi terkait;
- penyelenggara;
- jenis internal atau eksternal;
- waktu mulai;
- status aktif.

Atasan hanya dapat melihat dan mencari katalog. Pembuatan dan perubahan data pelatihan dilakukan HR.

### 5.4 Pengajuan Pelatihan

Menu ini menampilkan pengajuan Pegawai dan rekomendasi Atasan untuk bawahan langsung. Daftar berisi pegawai, pelatihan, status, alasan, catatan atasan, hasil pelatihan, dan waktu pengajuan.

Pengajuan berstatus menunggu atasan menyediakan dua aksi:

- **Setujui**: catatan bersifat opsional. Pengajuan diteruskan kepada HR.
- **Tolak**: alasan wajib diisi. Pengajuan dikembalikan kepada pegawai dan dapat diajukan ulang.

Setelah pengajuan Pegawai disetujui Atasan, HR menangani verifikasi dan pencatatan hasil. Rekomendasi yang dibuat melalui Hasil Merit langsung berstatus **Disetujui**; HR hanya memantau dan mencatat hasilnya. Atasan dapat terus memantau perubahan status, tetapi tidak dapat mengubah atau menghapus data yang sudah dibuat.

### 5.5 Mentoring

Menu ini menampilkan permintaan mentoring dari bawahan langsung. Daftar berisi pegawai, topik, target, status, jadwal yang diajukan, jadwal yang disetujui, hasil diskusi, dan tindak lanjut.

Permintaan baru menyediakan aksi:

- **Jadwalkan**: atasan menentukan jadwal mendatang dan dapat memberi catatan.
- **Tolak**: atasan wajib mengisi alasan.

Mentoring yang telah dijadwalkan menyediakan aksi **Catat Hasil**. Atasan wajib mengisi hasil diskusi dan tindak lanjut; sistem kemudian menandai mentoring selesai.

Atasan tidak dapat membuat, mengubah, atau menghapus permintaan mentoring secara langsung. Seluruh proses dilakukan melalui aksi pada baris permintaan.

## 6. Navigasi dan Akun

Sidebar dapat diciutkan pada tampilan desktop. Peringatan perubahan belum disimpan muncul bila pengguna meninggalkan formulir yang masih berubah.

Menu akun dipakai untuk melihat akun yang sedang aktif dan keluar dari aplikasi. Hanya akun aktif dengan peran Atasan yang dapat membuka Panel Atasan.

## 7. Ringkasan Hak Akses Atasan

- **Buat dan kelola dengan syarat:** Perintah Dinas dan KPI Pegawai.
- **Buat dan lihat:** Penilaian 360 untuk bawahan.
- **Verifikasi:** Hasil Merit bawahan.
- **Setujui atau tolak:** Pengajuan Pelatihan dari bawahan.
- **Rekomendasikan:** Pelatihan bagi bawahan berdasarkan Hasil Merit, langsung berstatus Disetujui.
- **Jadwalkan, tolak, dan selesaikan:** Mentoring bawahan.
- **Hanya lihat:** Riwayat Absensi, Kompetensi Pegawai, Target Karier, dan Katalog Pelatihan.
- **Lingkup data:** hanya data bawahan langsung atau data yang terkait dengan tindakan atasan yang sedang masuk.
