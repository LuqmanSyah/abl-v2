# Dokumen Perbaikan Absensi Dinas

## Ringkasan

Layanan absensi dinas sudah memiliki dasar yang baik. Sistem sudah memeriksa pengguna, lokasi, waktu, foto, dan mencegah pengiriman data yang sama secara berulang.

Namun, beberapa bagian masih dapat menghasilkan data absensi yang tidak sesuai. Perbaikan perlu dilakukan sebelum layanan dipakai penuh karena data absensi ikut memengaruhi penilaian kedisiplinan dan hasil merit pegawai.

## Mengapa Perbaikan Harus Dilakukan

### 1. Dinas beberapa hari hanya dapat memiliki satu absensi

Saat ini satu perjalanan dinas hanya dapat menyimpan satu absensi. Jika dinas berlangsung tiga hari, pegawai dapat absen pada hari pertama, tetapi absensi hari kedua dan ketiga tidak dapat disimpan dengan benar.

Dampaknya:

- Riwayat kehadiran pegawai tidak lengkap.
- Pegawai terlihat tidak hadir meskipun sudah mencoba absen.
- Nilai kedisiplinan dapat menjadi lebih rendah dari kondisi sebenarnya.
- HR harus memperbaiki data secara manual.

Masalah ini menjadi prioritas tertinggi karena langsung memengaruhi kebenaran data.

### 2. Pemeriksaan absensi ganda memakai tanggal server

Sistem menyediakan penyimpanan sementara ketika perangkat sedang offline. Data baru dikirim setelah koneksi kembali tersedia.

Saat data lama dikirim, sistem masih membandingkannya dengan tanggal saat data diterima server. Akibatnya, absensi dari hari sebelumnya dapat dianggap sebagai absensi hari ini atau tersimpan lebih dari sekali setelah aturan absensi harian diperbaiki.

Dampaknya:

- Riwayat absensi dapat tercatat pada pemeriksaan hari yang salah.
- Data ganda dapat muncul.
- Rekap harian menjadi tidak akurat.

Pemeriksaan harus mengikuti waktu saat pegawai melakukan absensi, bukan waktu saat server menerima data.

### 3. Alasan pemeriksaan dapat hilang setelah disetujui HR

Absensi dapat ditandai untuk diperiksa karena lokasi terlalu jauh, waktu terlambat, akurasi GPS buruk, atau waktu perangkat tidak sesuai.

Saat HR menyetujui data tersebut, status berubah menjadi valid. Alasan awal dapat tidak terlihat lagi dalam status akhir. Kondisi ini membuat data yang terlambat atau berada di luar lokasi terlihat sama dengan absensi normal.

Dampaknya:

- HR sulit mengetahui alasan awal pemeriksaan.
- Riwayat keputusan kurang jelas.
- Nilai kedisiplinan dapat memakai data tanpa konteks yang cukup.

Sistem perlu tetap menyimpan alasan awal walaupun HR menyetujui absensi.

### 4. Data pengenalan wajah belum diperiksa dengan cukup ketat

Sistem menerima data pengenalan wajah dari perangkat. Saat ini bentuk dan isi data tersebut belum diperiksa secara lengkap.

Dampaknya:

- Data rusak dapat menyebabkan proses absensi gagal.
- Data terlalu besar dapat membebani penyimpanan.
- Perbandingan wajah dapat memberikan hasil yang tidak dapat dipercaya.

Sistem perlu memastikan data memiliki bentuk, ukuran, dan isi yang sesuai sebelum disimpan.

### 5. Selisih kecil waktu perangkat langsung ditolak

Jam pada telepon pegawai dan server tidak selalu sama persis. Selisih beberapa detik atau menit masih wajar.

Saat ini waktu perangkat yang sedikit lebih maju dapat langsung ditolak. Padahal sistem sudah memiliki batas toleransi waktu.

Dampaknya:

- Pegawai gagal absen meskipun berada pada waktu dan lokasi yang benar.
- Pegawai harus mencoba ulang tanpa memahami penyebabnya.
- Keluhan operasional meningkat.

Selisih kecil sebaiknya diterima sesuai batas toleransi. Selisih besar tetap masuk pemeriksaan HR.

### 6. Pengiriman notifikasi masih menyatu dengan penyimpanan absensi

Setelah absensi disimpan, sistem dapat mengirim notifikasi kepada atasan. Jika layanan notifikasi mengalami gangguan, proses penyimpanan absensi juga dapat ikut terganggu atau dicoba ulang.

Dampaknya:

- Absensi dapat gagal karena layanan lain sedang bermasalah.
- Atasan dapat menerima notifikasi lebih dari sekali.
- Proses absensi terasa lambat.

Penyimpanan absensi dan pengiriman notifikasi perlu dipisahkan. Absensi harus tetap tersimpan walaupun notifikasi sedang bermasalah.

## Urutan Prioritas

1. Mendukung satu absensi per hari untuk dinas beberapa hari.
2. Memeriksa data ganda berdasarkan waktu absensi dilakukan.
3. Menjaga alasan awal saat HR memverifikasi absensi.
4. Memeriksa data pengenalan wajah sebelum diproses.
5. Menggunakan batas toleransi untuk selisih waktu perangkat.
6. Mengirim notifikasi setelah absensi berhasil tersimpan.

## Hasil yang Diharapkan

Setelah perbaikan:

- Pegawai dapat absen setiap hari selama perjalanan dinas.
- Sinkronisasi setelah offline tidak menghasilkan data salah atau ganda.
- HR tetap dapat melihat alasan absensi perlu diperiksa.
- Gangguan notifikasi tidak menggagalkan penyimpanan absensi.
- Selisih kecil jam perangkat tidak menghambat pegawai.
- Data kedisiplinan dan merit memakai riwayat absensi yang lebih akurat.

## Tingkat Kebutuhan

Urgensi perbaikan: **9 dari 10**.

Dua perbaikan pertama wajib selesai sebelum layanan dipakai penuh. Perbaikan lain dapat dikerjakan setelahnya, tetapi tetap penting untuk menjaga keandalan layanan dan mengurangi pekerjaan manual HR.

## Batas Dokumen

Dokumen ini menjelaskan alasan dan dampak perbaikan. Rincian teknis, perubahan database, serta daftar pengujian dibuat saat tahap pelaksanaan dimulai.
