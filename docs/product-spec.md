# Product Specification — ABL Sistem SDM

> - Status: Draft v0.1
> - Baseline implementasi: 29 Juli 2026
> - Jenis dokumen: *as-built product specification*

## 1. Cara Membaca Dokumen

Dokumen ini disusun dari implementasi aplikasi saat ini: route, panel, resource, model, service, aturan bisnis, scheduler, dan test.

Penanda yang digunakan:

- **As-built**: sudah tersedia pada implementasi saat ini.
- **TBD**: belum diputuskan dan perlu pembahasan.
- **Belum ada**: tidak ditemukan pada implementasi saat ini.

Jika dokumen lama bertentangan dengan dokumen ini, implementasi saat ini menjadi baseline sampai keputusan produk baru dibuat.

## 2. Ringkasan Produk

ABL adalah aplikasi web manajemen SDM dengan tiga layanan utama:

1. pengelolaan perjalanan dinas dan absensi berbasis lokasi;
2. pengelolaan KPI, penilaian kinerja, dan hasil merit;
3. pengembangan karier melalui kompetensi, pelatihan, dan mentoring.

Aplikasi menyediakan portal terpisah untuk Pegawai, Atasan, dan Admin SDM/HR. Data serta aksi dibatasi berdasarkan peran dan hubungan Atasan–Pegawai.

### 2.1 Hipotesis masalah

Bagian ini belum pernah ditetapkan secara eksplisit dan perlu dikonfirmasi.

ABL diasumsikan ingin menyelesaikan masalah berikut:

- proses SDM tersebar dan sulit dipantau;
- perjalanan dinas tidak memiliki bukti kehadiran yang konsisten;
- penilaian kinerja sulit ditelusuri sampai komponen pembentuk nilainya;
- pengembangan kompetensi, pelatihan, dan mentoring tidak terhubung;
- HR membutuhkan laporan serta riwayat aktivitas dalam satu sistem.

### 2.2 Tujuan produk

- Menyediakan alur kerja SDM berbasis peran.
- Membatasi data sesuai hubungan organisasi.
- Menyimpan bukti dan riwayat proses penting.
- Menghasilkan skor merit dari data KPI, disiplin, dan penilaian.
- Menghubungkan gap kompetensi dengan pelatihan atau mentoring.
- Memberi HR ringkasan lintas modul dan ekspor laporan.

### 2.3 Ukuran keberhasilan

**TBD.** Metrik belum ditentukan. Kandidat untuk dibahas:

- persentase perjalanan dinas dengan absensi lengkap;
- waktu rata-rata HR menyelesaikan pemeriksaan absensi;
- persentase KPI lengkap sebelum periode berakhir;
- waktu penyelesaian pengajuan pelatihan dan mentoring;
- jumlah transaksi yang masih membutuhkan proses manual di luar aplikasi.

## 3. Pengguna dan Hak Akses

| Area | Pegawai | Atasan | Admin SDM/HR |
|---|---|---|---|
| Akun | Masuk dan ubah profil sendiri | Masuk dan ubah profil sendiri | Masuk, ubah profil, kelola akun |
| Organisasi | Lihat data kepegawaian sendiri | Lihat data tim melalui modul terkait | Kelola unit, jabatan, pengguna, dan relasi Atasan |
| Perjalanan dinas | Lihat tugas sendiri | Buat dan kelola tugas bawahan langsung | Monitor semua tugas |
| Absensi dinas | Kirim dan lihat absensi sendiri | Monitor absensi bawahan | Monitor dan verifikasi absensi bermasalah |
| KPI | Lihat KPI sendiri | Kelola KPI bawahan | Monitor seluruh KPI |
| Umpan balik kinerja | Buat dan lihat penilaian sendiri | Buat dan lihat penilaian sendiri | Monitor seluruh penilaian |
| Merit | Lihat hasil yang sudah dipublikasikan | Lihat dan verifikasi hasil bawahan | Verifikasi serta publikasikan semua hasil |
| Kompetensi | Lihat profil sendiri | Monitor kompetensi bawahan | Kelola kamus, standar, dan level pegawai |
| Target karier | Kelola target sendiri | Monitor target bawahan | Monitor seluruh target |
| Pelatihan | Lihat katalog dan ajukan pelatihan | Setujui, tolak, atau rekomendasikan | Kelola katalog, verifikasi, dan catat hasil |
| Mentoring | Ajukan dan pantau | Jadwalkan, tolak, dan catat hasil | Monitor seluruh mentoring |
| Laporan dan audit | Tidak tersedia | Tidak tersedia | Lihat laporan, ekspor, dan riwayat aktivitas |

Pengguna nonaktif tidak dapat masuk atau mengakses panel.

## 4. Ruang Lingkup As-Built

### 4.1 Organisasi dan akun

- Tiga peran: Pegawai, Atasan, dan Admin SDM/HR.
- Satu halaman login mengarahkan pengguna ke panel sesuai perannya.
- HR mengelola pengguna, unit kerja, jabatan, Atasan langsung, delegasi, dan status aktif.
- Jabatan pengguna harus berasal dari unit kerja pengguna.
- Atasan yang masih memiliki bawahan tidak dapat dinonaktifkan atau diubah ke peran lain.
- Atasan langsung Pegawai harus merupakan pengguna aktif dengan peran Atasan.
- Pengguna dapat mengubah nama, email, telepon, dan kata sandi sendiri.
- Nomor pegawai, peran, unit, dan jabatan hanya ditampilkan pada profil; tidak dapat diubah sendiri.

### 4.2 Perjalanan dinas

#### Alur

1. HR mengelola lokasi dinas beserta koordinat dan radius aktif.
2. Atasan membuat tugas untuk bawahan langsung.
3. Sistem menyalin nama lokasi, alamat, koordinat, dan radius ke tugas.
4. Tugas langsung berstatus **Ditugaskan**; tidak ada alur persetujuan terpisah.
5. Pegawai menerima notifikasi penugasan dan melihat tugas pada panelnya.
6. Atasan dapat mengubah atau membatalkan tugas selama aturan perubahan masih terpenuhi.

#### Aturan

- Pegawai wajib merupakan bawahan langsung Atasan pembuat tugas.
- Status tugas hanya **Ditugaskan** atau **Dibatalkan**.
- Tugas dapat diubah atau dibatalkan oleh Atasan pemilik jika:
  - status masih Ditugaskan;
  - waktu mulai masih di masa depan;
  - belum ada absensi pada hari berjalan.
- Lokasi tugas tidak dapat diubah setelah tugas memiliki absensi.
- Tugas tidak dapat dihapus melalui panel.

### 4.3 Absensi dinas

Absensi saat ini adalah **absensi perjalanan dinas**, bukan absensi kehadiran kerja harian.

#### Data yang dikirim Pegawai

- waktu dari perangkat;
- latitude dan longitude;
- akurasi GPS;
- foto langsung dari kamera;
- identitas tugas dan pengguna dari sesi aplikasi.

Foto diberi watermark nama Pegawai, waktu, koordinat, dan lokasi dinas sebelum dikirim. Ukuran maksimal foto 5 MB.

#### Alur pencatatan

1. Pegawai membuka tugas miliknya yang masih berstatus Ditugaskan.
2. Browser membaca GPS dan mengambil foto dari kamera.
3. Server memvalidasi kepemilikan tugas, status, jadwal, lokasi, waktu, dan duplikasi.
4. Server menghitung jarak dari lokasi tugas.
5. Sistem menyimpan absensi dan menentukan status.
6. Absensi yang memerlukan pemeriksaan mengirim notifikasi kepada HR.
7. HR dapat mengubah status **Memerlukan Pemeriksaan** menjadi **Valid**.

#### Aturan status

| Kondisi | Status |
|---|---|
| Jarak melebihi radius tugas | Memerlukan Pemeriksaan |
| Selisih waktu perangkat dan server melebihi toleransi | Memerlukan Pemeriksaan |
| Waktu absensi melewati waktu selesai tugas tanpa kondisi pemeriksaan | Terlambat |
| Semua pemeriksaan terpenuhi | Valid |

Toleransi waktu perangkat saat ini 15 menit dan dapat diubah melalui konfigurasi.

#### Aturan lain

- Absensi ditolak sebelum waktu mulai tugas.
- Absensi setelah waktu selesai tetap diterima dengan status Terlambat.
- Satu absensi disimpan per tanggal per tugas.
- Pengiriman ulang pada tanggal dan tugas sama mengembalikan absensi lama.
- Tanggal absensi berasal dari waktu perangkat yang dikirim.
- Akurasi GPS disimpan, tetapi belum memengaruhi status.
- Hanya absensi berstatus Valid yang dihitung sebagai disiplin pada merit.
- Foto disimpan pada penyimpanan privat.
- Pegawai hanya dapat melihat fotonya sendiri.
- Atasan hanya dapat melihat foto bawahan pada tugasnya.
- HR dapat melihat seluruh foto.
- Riwayat absensi tidak dapat dibuat, diubah, atau dihapus dari panel.

### 4.4 KPI, penilaian, dan merit

#### Periode dan indikator

- HR membuat periode penilaian.
- Periode menyimpan bobot KPI, disiplin, penilaian Atasan, penilaian 360, dan dasar bonus.
- Total bobot komponen merit wajib 100%.
- Tanggal selesai periode tidak boleh sebelum tanggal mulai.
- HR membuat indikator KPI untuk setiap periode.
- Total bobot indikator dalam satu periode tidak boleh melebihi 100%.
- Periode dan indikator terkunci setelah hasil merit terkait dipublikasikan.

#### KPI Pegawai

- Atasan membuat KPI untuk bawahan langsung.
- Target wajib lebih dari 0; capaian tidak boleh negatif.
- Indikator harus berasal dari periode yang dipilih.
- Pegawai dan HR hanya memonitor KPI.
- KPI dapat diubah atau dihapus Atasan sebelum hasil merit dipublikasikan.
- Pembuatan, perubahan, dan penghapusan KPI dicatat pada riwayat aktivitas.

#### Umpan balik kinerja

- Nilai menggunakan skala 1–5.
- Atasan dapat menilai bawahan langsung.
- Pegawai dapat menilai Atasan langsung.
- Rekan kerja dapat saling menilai jika berada pada unit sama dan bukan pengguna yang sama.
- Penilaian yang sudah dikirim tidak dapat diubah atau dihapus.

#### Perhitungan merit

- Nilai KPI adalah rata-rata berbobot capaian terhadap target, dengan capaian per indikator dibatasi maksimal 120%.
- Nilai disiplin adalah jumlah tanggal dengan absensi Valid dibagi jumlah tanggal perjalanan dinas yang selesai dalam periode.
- Nilai Atasan adalah rata-rata penilaian Atasan kepada Pegawai, dikonversi dari skala 1–5 menjadi 0–100.
- Nilai 360 adalah rata-rata tipe penilaian Pegawai kepada Atasan dan rekan kerja yang ditemukan untuk subjek penilaian, dikonversi menjadi 0–100.
- Nilai komponen yang tidak memiliki data menjadi 0.
- Total merit adalah penjumlahan seluruh nilai komponen sesuai bobot periode.
- Estimasi bonus adalah dasar bonus dikali total merit.
- Hasil dapat dihitung ulang sebelum verifikasi.
- Setelah Atasan memverifikasi, hasil tidak dapat dihitung ulang.
- HR hanya dapat memverifikasi setelah Atasan.
- Verifikasi HR sekaligus mempublikasikan hasil kepada Pegawai.
- Pegawai hanya melihat hasil yang sudah dipublikasikan.

Perhitungan otomatis dijadwalkan setiap tanggal 1 pukul 00.05.

### 4.5 Kompetensi dan rencana karier

- HR mengelola kamus kompetensi.
- HR menetapkan standar kompetensi jabatan pada level 1–5.
- HR mencatat level kompetensi Pegawai pada skala 1–5.
- Pegawai melihat profil kompetensinya; Atasan melihat bawahan; HR melihat semua.
- Setiap Pegawai dapat memiliki satu target karier.
- Jabatan tujuan wajib memiliki level lebih tinggi dari jabatan saat ini.
- Sistem membandingkan level Pegawai dengan standar jabatan tujuan.
- Gap menampilkan level saat ini, level wajib, selisih, dan rekomendasi.
- Rekomendasi menggunakan pelatihan aktif yang terkait kompetensi; jika tidak ada, sistem menyarankan mentoring.

### 4.6 Pelatihan

#### Katalog

- HR mengelola katalog pelatihan internal atau eksternal.
- Pelatihan dapat dihubungkan dengan kompetensi.
- Pegawai dan Atasan hanya melihat pelatihan aktif.

#### Pengajuan Pegawai

Alur status:

`Menunggu Atasan` → `Menunggu HR` → `Disetujui` → `Selesai`

Atasan juga dapat mengubah `Menunggu Atasan` menjadi `Ditolak`. Pegawai dapat memperbarui alasan dan mengajukan ulang data yang ditolak.

- Pegawai hanya dapat mengajukan untuk dirinya sendiri kepada Atasan langsung.
- Hanya pelatihan aktif yang dapat diajukan.
- Atasan memberi persetujuan atau penolakan beserta catatan.
- HR memverifikasi pengajuan yang disetujui Atasan.
- HR mencatat hasil sebelum menandai pelatihan Selesai.

#### Rekomendasi Atasan

- Atasan dapat merekomendasikan pelatihan kepada bawahan berdasarkan hasil merit.
- Rekomendasi langsung berstatus Disetujui.
- Pelatihan yang sama tidak dapat direkomendasikan jika pernah diajukan atau direkomendasikan untuk Pegawai tersebut.
- Sumber hasil merit dan nilai komponennya dicatat pada riwayat aktivitas.

### 4.7 Mentoring

Alur status:

`Menunggu Atasan` → `Dijadwalkan` → `Selesai`

Atasan juga dapat mengubah `Menunggu Atasan` menjadi `Ditolak`.

- Pegawai mengajukan topik, target, dan jadwal kepada Atasan langsung.
- Jadwal pengajuan dan jadwal yang disetujui tidak boleh berada di masa lalu.
- Atasan menetapkan jadwal dan catatan.
- Atasan mencatat hasil diskusi serta tindak lanjut sebelum menyelesaikan mentoring.
- HR hanya memonitor.

### 4.8 Laporan, notifikasi, audit, dan operasi

#### Laporan HR

HR dapat melihat dan mengekspor laporan dengan:

- filter periode, unit, dan jabatan;
- pemilihan kolom;
- pengelompokan berdasarkan unit atau jabatan;
- format CSV, PDF, dan XLSX.

Kolom tersedia: nomor pegawai, nama, unit, jabatan, total absensi, absensi Valid, skor merit, jumlah pelatihan, pelatihan selesai, jumlah mentoring, dan mentoring selesai.

Laporan email dijadwalkan setiap tanggal 1 pukul 01.00.

#### Notifikasi

Notifikasi digunakan untuk penugasan dinas, absensi yang perlu diperiksa, pengingat KPI, hasil merit, pengajuan pelatihan, dan mentoring. Panel membaca notifikasi database secara berkala.

#### Audit

HR memiliki daftar riwayat aktivitas yang menampilkan waktu, pengguna, aksi, jenis data, dan ID data. Log tidak dapat dibuat melalui panel.

#### Proses terjadwal

- backup database setiap hari pukul 02.00;
- eskalasi notifikasi persetujuan tertunda setiap hari pukul 06.00;
- pengingat KPI setiap hari pukul 09.00;
- perhitungan merit setiap tanggal 1 pukul 00.05;
- pengiriman laporan HR setiap tanggal 1 pukul 01.00.

## 5. Batas Implementasi Saat Ini

Fitur berikut belum ditemukan pada alur aktif saat ini:

- absensi kerja harian di luar perjalanan dinas;
- izin, sakit, cuti, lembur, dan jadwal kerja;
- face recognition atau liveness detection;
- deteksi mock location;
- antrean absensi offline dan sinkronisasi otomatis;
- aturan status berdasarkan nilai akurasi GPS;
- penolakan atau status Tidak Valid oleh HR;
- koreksi absensi oleh Atasan atau Pegawai;
- alur persetujuan sebelum perjalanan dinas diterbitkan;
- payroll, rekrutmen, dan administrasi cuti.

Halaman absensi menampilkan status jaringan, tetapi pengiriman tetap membutuhkan koneksi ke server.

## 6. Keputusan Produk yang Masih TBD

### 6.1 Arah produk

1. Apakah fokus produk tetap tiga layanan besar, atau satu modul harus menjadi produk utama?
2. Apakah target penggunaan hanya demonstrasi mata kuliah atau simulasi sistem organisasi nyata?
3. Data apa yang wajib terlihat pada dashboard setiap peran?
4. Metrik apa yang menentukan proyek dianggap berhasil?

### 6.2 Absensi

1. Apakah absensi hanya untuk perjalanan dinas atau juga kehadiran kerja harian?
2. Untuk tugas multi-hari, apakah wajib satu absensi setiap hari, termasuk akhir pekan?
3. Sampai kapan absensi terlambat masih boleh dikirim? Saat ini tidak ada batas akhir.
4. Apakah tanggal absensi boleh ditentukan dari jam perangkat, atau wajib dari jam server?
5. Berapa batas akurasi GPS yang masih dapat dipercaya?
6. Apakah lokasi di luar radius harus ditolak, diperiksa HR, atau tetap dianggap Terlambat?
7. Apakah HR membutuhkan aksi Valid, Tolak/Tidak Valid, dan batalkan verifikasi?
8. Apakah Atasan boleh mengoreksi absensi bawahan?
9. Apakah diperlukan alasan, catatan, atau bukti tambahan ketika absensi bermasalah?
10. Apakah diperlukan face recognition, liveness detection, atau deteksi mock location?
11. Apakah absensi offline wajib? Jika ya, bagaimana mencegah manipulasi waktu dan lokasi?
12. Berapa lama foto dan koordinat boleh disimpan?
13. Bagaimana menangani dua tugas yang waktunya tumpang tindih?
14. Apakah status awal harus tetap tersimpan setelah HR mengubah status menjadi Valid?

### 6.3 Merit

1. Apakah komponen tanpa data harus bernilai 0 atau dikeluarkan dari pembobotan?
2. Apakah hanya absensi Valid yang dihitung, atau Terlambat mendapat nilai sebagian?
3. Apakah disiplin memang hanya berasal dari perjalanan dinas?
4. Siapa yang boleh memicu hitung ulang dan melihat rincian formula?
5. Apakah estimasi bonus hanya simulasi atau akan menjadi data resmi?
6. Apakah definisi penilaian 360 saat ini sesuai tujuan produk?

### 6.4 Pelatihan, mentoring, dan approval

1. Apakah rekomendasi pelatihan Atasan boleh langsung Disetujui tanpa verifikasi HR?
2. Apakah pengajuan pelatihan boleh dibatalkan Pegawai?
3. Apakah mentoring yang ditolak boleh diajukan ulang?
4. Apakah delegasi Atasan harus tersedia pada panel? Dukungan model ada, tetapi daftar dan tombol panel masih membatasi data ke Atasan asli.
5. Apakah rantai persetujuan harus mengubah pemilik atau status proses? Saat ini konfigurasi rantai hanya dipakai untuk menentukan penerima notifikasi eskalasi.

## 7. Acceptance Baseline

Baseline berikut harus tetap terpenuhi selama keputusan TBD belum mengubah requirement:

- setiap peran hanya dapat membuka panel dan data yang diizinkan;
- pengguna nonaktif tidak dapat masuk;
- Atasan hanya dapat memberi tugas dan KPI kepada bawahan langsung;
- Pegawai hanya dapat mengirim absensi untuk tugasnya sendiri;
- absensi menyimpan foto privat, lokasi, jarak, waktu, dan status;
- duplikasi absensi pada tanggal dan tugas sama tidak membuat data baru;
- HR hanya dapat memverifikasi absensi yang Memerlukan Pemeriksaan;
- bobot merit selalu berjumlah 100%;
- hasil merit hanya terlihat oleh Pegawai setelah publikasi HR;
- pengajuan pelatihan dan mentoring hanya berpindah melalui status yang valid;
- perubahan proses penting menghasilkan riwayat aktivitas;
- laporan HR hanya dapat diakses pengguna HR.

## 8. Langkah Berikutnya

1. Tinjau bagian hipotesis masalah dan tujuan.
2. Jawab keputusan TBD, dimulai dari batas layanan absensi.
3. Ubah keputusan yang disetujui menjadi requirement final.
4. Bandingkan requirement final dengan implementasi.
5. Susun backlog untuk gap yang benar-benar diperlukan.
