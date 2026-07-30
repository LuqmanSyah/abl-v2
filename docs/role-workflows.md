# Workflow Pengguna Saat Ini

Dokumen ini mencatat workflow yang benar-benar tersedia pada aplikasi saat ini. Semua role masuk melalui `/app`; hanya pengguna aktif yang dapat mengakses panel.

## Ringkasan Akses

| Modul | Pegawai | Atasan | Admin SDM/HR |
|---|---|---|---|
| Dashboard | Ringkasan data sendiri | Ringkasan tim langsung | Ringkasan operasional |
| Organisasi | Tidak ada akses | Tidak ada akses | Kelola pengguna, unit, dan jabatan |
| Perjalanan dinas | Lihat dan absen pada tugas sendiri | Buat, lihat, ubah, atau batalkan tugas bawahan | Lihat semua |
| Absensi | Buat harian dan lihat milik sendiri | Lihat absensi dari tugas yang dibuatnya | Lihat semua dan verifikasi |
| KPI | Lihat milik sendiri | Kelola KPI bawahan | Lihat semua |
| Periode penilaian | Tidak ada akses | Tidak ada akses | Buat, ubah, hitung, dan publikasikan |
| Hasil merit | Lihat hasil sendiri yang sudah terpublikasi | Lihat hasil bawahan | Lihat semua |
| Rencana pengembangan | Lihat milik sendiri | Kelola milik bawahan | Kelola semua |
| Pengajuan pengembangan | Buat dan lihat milik sendiri | Setujui, tolak, atau selesaikan milik bawahan | Lihat semua dan selesaikan |
| Laporan dan log | Tidak ada akses | Tidak ada akses | Ekspor CSV dan lihat riwayat aktivitas |

## 1. Pegawai

### Dashboard

Pegawai melihat:

- jumlah perjalanan dinas aktif;
- jumlah KPI miliknya;
- jumlah pengajuan pengembangan yang masih menunggu.

### Perjalanan Dinas dan Absensi

1. Pegawai membuka perjalanan dinas miliknya.
2. Tombol **Lakukan Absensi** tersedia selama tanggal dinas aktif dan absensi hari itu belum ada.
3. Pegawai mengizinkan kamera dan lokasi browser.
4. Pegawai mengirim foto, koordinat, dan akurasi GPS.
5. Server menetapkan tanggal dan waktu penerimaan.
6. Satu perjalanan dinas hanya menerima satu absensi per tanggal kalender.
7. Absensi dalam radius dengan akurasi memadai langsung berstatus `valid`.
8. Absensi di luar radius atau dengan akurasi lebih dari 150 meter berstatus `needs_review`.

Pegawai hanya dapat melihat perjalanan, absensi, dan foto miliknya. Pegawai tidak dapat membuat, mengubah, atau membatalkan perjalanan dinas.

### KPI dan Merit

1. Pegawai melihat target, capaian, dan catatan KPI miliknya.
2. Pegawai tidak dapat mengubah KPI.
3. Setelah HR mempublikasikan periode, Pegawai dapat melihat nilai KPI, kehadiran, dan nilai merit akhirnya.

### Pengembangan

1. Pegawai melihat rencana pengembangan miliknya.
2. Jika memiliki Atasan langsung, Pegawai dapat mengajukan `pelatihan` atau `mentoring`.
3. Pegawai mengisi judul, alasan, dan jadwal usulan opsional.
4. Pengajuan dibuat dengan status `pending`.
5. Pegawai memantau hasil pengajuan, tetapi tidak dapat mengubah atau menghapusnya.

## 2. Atasan

### Dashboard

Atasan melihat:

- jumlah perjalanan dinas aktif yang dibuatnya;
- jumlah bawahan aktif yang belum memiliki KPI pada periode berjalan;
- jumlah pengajuan bawahan yang masih menunggu.

### Perjalanan Dinas

1. Atasan memilih satu atau lebih bawahan aktif.
2. Atasan mengisi lokasi, alamat, titik peta, radius, tanggal mulai, dan tanggal selesai.
3. Satu pengiriman membuat satu record perjalanan dinas per Pegawai dalam satu transaksi.
4. Atasan dapat melihat perjalanan dan absensi dari tugas yang dibuatnya.
5. Perjalanan hanya dapat diubah atau dibatalkan oleh Atasan pembuat sebelum tanggal mulai dan sebelum memiliki absensi.

HR tidak perlu menyetujui perjalanan dinas; tugas langsung aktif setelah dibuat.

### KPI

1. Atasan memilih periode yang belum dipublikasikan.
2. Atasan membuat KPI untuk bawahan aktif: nama KPI, target, capaian, dan catatan.
3. Atasan dapat memperbarui atau menghapus KPI miliknya selama periode belum dipublikasikan.
4. Setelah periode dipublikasikan, KPI terkunci.
5. Atasan dapat melihat hasil merit bawahan langsung.

### Pengembangan

1. Atasan membuat atau memperbarui rencana pengembangan bawahan.
2. Atasan meninjau pengajuan `pending` dari bawahan langsung.
3. Atasan memilih **Setujui** atau **Tolak**; alasan wajib diisi saat menolak.
4. Pengajuan `approved` dapat ditandai **Selesai** oleh Atasan.

Atasan tidak dapat menghapus rencana pengembangan dan tidak dapat mengelola data organisasi atau periode penilaian.

## 3. Admin SDM/HR

### Dashboard

HR melihat:

- jumlah absensi yang perlu diperiksa;
- jumlah periode yang belum dipublikasikan;
- jumlah pengajuan pengembangan yang masih `pending` atau `approved`.

### Organisasi

1. HR membuat dan memperbarui akun Pegawai, Atasan, dan HR.
2. HR menetapkan unit, jabatan, Atasan langsung, nomor Pegawai, dan status aktif.
3. HR membuat, memperbarui, atau menghapus unit dan jabatan.
4. Akun pengguna tidak dihapus; HR menonaktifkannya bila diperlukan.

Jabatan harus berasal dari unit pengguna. Atasan yang masih memiliki bawahan tidak dapat dinonaktifkan atau diubah perannya.

### Perjalanan Dinas dan Absensi

1. HR memonitor seluruh perjalanan dinas dan absensi.
2. HR membuka foto dan detail lokasi absensi.
3. Absensi berstatus `needs_review` dapat diverifikasi menjadi `valid`.

HR tidak membuat perjalanan dinas, tidak mengirim absensi, dan tidak mengubah absensi yang sudah valid.

### Periode, KPI, dan Merit

1. HR membuat periode penilaian dengan tanggal mulai dan selesai.
2. Selama belum dipublikasikan, periode dapat diubah dan Atasan dapat mengisi KPI.
3. HR menekan **Hitung dan Publikasikan**.
4. Sistem membuat hasil untuk seluruh Pegawai aktif:
   - nilai KPI = rata-rata capaian/target, maksimal 120%;
   - nilai kehadiran = absensi harian valid/hari dinas wajib;
   - nilai akhir = 80% KPI + 20% kehadiran.
5. Periode dan KPI terkunci setelah publikasi.

HR dapat melihat seluruh KPI dan hasil merit, tetapi pengisian KPI tetap menjadi tugas Atasan.

### Pengembangan

1. HR membuat atau memperbarui rencana pengembangan Pegawai mana pun.
2. HR melihat seluruh pengajuan pelatihan dan mentoring.
3. HR dapat menandai pengajuan `approved` sebagai `completed`.

Persetujuan atau penolakan awal tetap dilakukan oleh Atasan langsung.

### Laporan dan Audit

1. HR membuka daftar Pegawai dan memilih periode.
2. HR dapat menerapkan filter unit atau jabatan.
3. HR mengekspor CSV berisi nomor Pegawai, nama, unit, jabatan, absensi valid, jumlah KPI, nilai merit, dan jumlah pengajuan pengembangan.
4. HR melihat riwayat aktivitas penting pada menu **Riwayat Aktivitas**.

## Transisi Status Utama

```text
Perjalanan dinas
active ──Atasan, sebelum mulai dan sebelum ada absensi──> cancelled

Absensi
valid
needs_review ──HR verifikasi──> valid

Pengajuan pengembangan
pending ──Atasan──> approved ──Atasan/HR──> completed
       └─Atasan──> rejected

Periode penilaian
draft ──HR hitung dan publikasikan──> published
```

Tidak tersedia aksi untuk mengembalikan status yang sudah selesai, ditolak, dibatalkan, atau dipublikasikan.
