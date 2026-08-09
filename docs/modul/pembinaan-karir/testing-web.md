# Testing Web Pembinaan Karir

[Kembali ke panduan utama](../../testing-web-sistem-sdm.md)

## 1. Kompetensi dan Gap Karier

### 1.1 Siapkan jabatan dan standar kompetensi

Login sebagai HR.

1. Buka Organisasi > Jabatan > Buat.
2. Buat dua jabatan pada Unit kerja `Operasional`:
   - `Direktur Tanpa Standar`, Level `8`;
   - `Direktur Web`, Level `9`.
3. Buka Pengembangan > Kompetensi > Buat.
4. Buat kompetensi `Kepemimpinan Web`.
5. Buka Standar Kompetensi Jabatan > Buat.
6. Pilih Jabatan `Direktur Web`, Kompetensi `Kepemimpinan Web`, dan Level wajib `4 — Membimbing orang lain`, lalu simpan.

Level kompetensi wajib dipilih dari rubrik berikut, bukan diisi sebagai angka bebas:

- `1 — Memahami konsep dasar`;
- `2 — Menerapkan dengan arahan`;
- `3 — Menerapkan secara mandiri`;
- `4 — Membimbing orang lain`;
- `5 — Menetapkan strategi dan standar`.

### 1.2 Uji target, standar kosong, dan kompetensi belum dinilai

1. Login sebagai Pegawai 1.
2. Buka Pengembangan > Rencana Karier > Buat.
3. Pastikan pilihan Jabatan tujuan hanya memuat jabatan dengan level lebih tinggi dari jabatan saat ini.
4. Pilih `Direktur Tanpa Standar`, lalu simpan.
5. Kolom Gap dan rekomendasi wajib menampilkan:

   ```text
   Standar kompetensi jabatan belum ditetapkan.
   ```

6. Tombol Buat tidak boleh tersedia lagi karena Pegawai hanya boleh memiliki satu target aktif.
7. Edit target tersebut dan ubah Jabatan tujuan menjadi `Direktur Web`.
8. Kolom Gap dan rekomendasi wajib menampilkan:

   ```text
   Kepemimpinan Web: Belum dinilai/4 — Ajukan mentoring
   ```

### 1.3 Uji create, update, tanggal masa depan, dan nilai terbaru

1. Login sebagai HR. Buka Pengembangan > Pengelolaan Kompetensi Pegawai > Buat.
2. Pilih Pegawai `Pegawai Demo 1`, Kompetensi `Kepemimpinan Web`, Level saat ini `2 — Menerapkan dengan arahan`, Tanggal penilaian hari ini, dan Catatan `Asesmen awal`.
3. Simpan.
4. Buka Laporan & Audit > Riwayat Aktivitas. Baris terbaru wajib berisi:
   - Pengguna: `Admin SDM`;
   - Aksi: `competency.created`.
5. Login sebagai Pegawai 1 dan muat ulang Rencana Karier. Gap wajib menjadi:

   ```text
   Kepemimpinan Web: 2/4 — Ajukan mentoring
   ```

6. Login sebagai HR. Edit kompetensi pegawai tadi dan ubah Tanggal penilaian menjadi besok.
7. Simpan wajib ditolak dengan pesan:

   ```text
   Tanggal penilaian kompetensi tidak boleh di masa depan.
   ```

8. Ubah tanggal kembali ke hari ini, Level saat ini menjadi `3 — Menerapkan secara mandiri`, dan Catatan menjadi `Asesmen ulang`, lalu simpan.
9. Riwayat Aktivitas wajib memiliki `competency.updated` oleh `Admin SDM`.
10. Login sebagai Pegawai 1 dan muat ulang Rencana Karier. Gap wajib berubah menjadi `3/4`. Sistem memakai nilai terkini.

### 1.4 Uji kompetensi master yang sedang dipakai

1. Login sebagai HR. Buka Pengembangan > Kompetensi.
2. Pada baris `Kepemimpinan Web`, aksi Hapus tidak boleh tersedia karena kompetensi sudah dipakai oleh standar jabatan dan asesmen Pegawai.
3. Aksi Edit tetap boleh digunakan.

## 2. Pelatihan

### 2.1 Uji alur persetujuan dan level tidak naik otomatis

1. Login sebagai HR. Buka Pengembangan > Pengelolaan Pelatihan > Buat.
2. Buat pelatihan `Leadership Web`, Jenis `Internal`, Kompetensi terkait `Kepemimpinan Web`, Aktif ya. Kosongkan Mulai dan Selesai agar hasil dapat langsung dicatat pada skenario ini.
3. Login sebagai Pegawai 1 dan muat ulang Rencana Karier. Rekomendasi gap wajib berubah dari `Ajukan mentoring` menjadi `Leadership Web`.
4. Buka Pengajuan Pelatihan > Buat, pilih `Leadership Web`, isi alasan, lalu simpan.
5. Status wajib `Menunggu Atasan`.
6. Buka dashboard Pegawai. Tabel Riwayat Pengajuan Pelatihan wajib menampilkan nama `Leadership Web`, bukan nilai kosong.
7. Login sebagai Atasan. Buka Persetujuan Pelatihan dan klik `Setujui` > `Setujui Pengajuan`.
8. Status wajib berubah menjadi `Menunggu HR`.
9. Login sebagai HR. Buka Verifikasi & Hasil Pelatihan dan klik `Verifikasi HR` > `Verifikasi Pengajuan`.
10. Status wajib berubah menjadi `Disetujui`.
11. Pada baris sama, klik `Catat Hasil`, isi hasil, lalu klik `Simpan Hasil`.
12. Status wajib berubah menjadi `Selesai`.
13. Login sebagai Pegawai 1. Buka Profil Kompetensi dan Rencana Karier.
14. Level wajib tetap `3`; gap wajib tetap `3/4`. Pelatihan selesai tidak menaikkan level otomatis.

HR tetap harus melakukan asesmen ulang untuk menaikkan level.

### 2.2 Uji pelatihan tidak tersedia dan belum selesai

Login sebagai HR dan buat tiga pelatihan berikut:

- `Leadership Kedaluwarsa`: Mulai dua hari lalu, Selesai kemarin, Aktif ya;
- `Leadership Nonaktif`: tanggal kosong, Aktif tidak;
- `Leadership Mendatang`: Mulai hari ini, Selesai besok, Aktif ya.

Lanjutkan pengujian:

1. Login sebagai Pegawai 1. Buka Katalog Pelatihan dan form Pengajuan Pelatihan.
2. `Leadership Kedaluwarsa` dan `Leadership Nonaktif` tidak boleh terlihat atau dapat dipilih.
3. Ajukan `Leadership Mendatang`.
4. Login sebagai Atasan dan setujui pengajuan tersebut.
5. Login sebagai HR. Buka Verifikasi & Hasil Pelatihan dan verifikasi pengajuan tersebut.
6. Klik `Catat Hasil` sebelum waktu Selesai, isi hasil, lalu simpan.
7. Penyelesaian wajib ditolak dengan pesan:

   ```text
   Pelatihan belum dapat diselesaikan.
   ```

8. Status wajib tetap `Disetujui`.

### 2.3 Uji rekomendasi Atasan, penolakan HR, dan pengajuan ulang

Prasyarat: selesaikan [alur publikasi merit](../merit-system/testing-web.md#25-buktikan-alur-verifikasi-dan-publikasi) sampai hasil `WEB Merit 01` untuk `Pegawai Demo 1` dipublikasikan.

1. Login sebagai HR dan buat pelatihan aktif `Leadership Rekomendasi` tanpa tanggal Selesai.
2. Login sebagai Atasan. Buka Kinerja > Verifikasi Merit.
3. Pada hasil `WEB Merit 01` milik `Pegawai Demo 1`, klik `Rekomendasikan Pelatihan`.
4. Pilih `Leadership Rekomendasi`, isi Alasan rekomendasi, lalu klik `Rekomendasikan`.
5. Notifikasi wajib menyatakan `Pelatihan direkomendasikan kepada HR`. Pengajuan belum boleh langsung berstatus Disetujui.
6. Login sebagai HR. Buka Verifikasi & Hasil Pelatihan. Pengajuan wajib berstatus `Menunggu HR`.
7. Klik `Tolak HR`, isi alasan `Anggaran belum tersedia`, lalu klik `Tolak Pengajuan`.
8. Status wajib berubah menjadi `Ditolak` dan Catatan HR menampilkan alasan tersebut.
9. Login sebagai Pegawai 1. Buka Pengajuan Pelatihan dan klik `Ajukan Ulang`.
10. Isi alasan terbaru, lalu klik `Kirim Ulang`.
11. Status wajib berubah menjadi `Menunggu Atasan` dan Catatan HR kembali kosong.
12. Login sebagai Atasan dan setujui pengajuan. Status wajib kembali menjadi `Menunggu HR`.

## 3. Mentoring

### 3.1 Uji pengajuan dan penjadwalan

1. Login sebagai Pegawai 1. Buka Pengembangan > Pengajuan Mentoring > Buat.
2. Pilih Kompetensi terkait `Kepemimpinan Web`, isi Topik `Pendalaman kepemimpinan`, Target `Mampu memimpin tim`, dan Jadwal yang diajukan beberapa menit dari sekarang.
3. Simpan. Status wajib `Menunggu Atasan` dan kolom Kompetensi wajib menampilkan `Kepemimpinan Web`.
4. Login sebagai Atasan. Pada dashboard, cari Persetujuan Mentoring Tertunda lalu klik `Atur Jadwal`.
5. Tautan wajib membuka Pengembangan > Pengelolaan Mentoring tanpa error halaman.
6. Pada baris pengajuan, klik `Jadwalkan`, isi Jadwal beberapa menit dari sekarang, lalu klik `Simpan Jadwal`.
7. Status wajib berubah menjadi `Dijadwalkan`.

### 3.2 Uji penyelesaian sesuai jadwal

1. Sebelum Jadwal disetujui terlewati, klik `Catat Hasil`, isi Hasil diskusi dan Tindak lanjut, lalu klik `Simpan Hasil`.
2. Penyelesaian wajib ditolak dengan pesan:

   ```text
   Mentoring tidak dapat diproses pengguna ini.
   ```

3. Status wajib tetap `Dijadwalkan`.
4. Setelah waktu Jadwal terlewati, ulangi `Catat Hasil`.
5. Status wajib berubah menjadi `Selesai`; kolom Hasil dan Tindak lanjut wajib terisi.
6. Login sebagai HR. Buka Pengembangan > Riwayat Mentoring.
7. Data mentoring wajib terlihat, tetapi HR tidak memiliki aksi Jadwalkan, Tolak, atau Catat Hasil.

## 4. Audit Delete

1. Login sebagai HR. Hapus data `Kepemimpinan Web` milik `Pegawai Demo 1` dari Pengelolaan Kompetensi Pegawai.
2. Buka Riwayat Aktivitas.
3. Baris terbaru wajib memiliki Aksi `competency.deleted` dan Pengguna `Admin SDM`.
4. Login sebagai Pegawai 1 dan muat ulang Rencana Karier. Gap wajib kembali menampilkan `Belum dinilai/4`.
5. Kompetensi master `Kepemimpinan Web` tetap tidak dapat dihapus karena masih dipakai oleh standar, pelatihan, dan mentoring.

## Batas Pengujian Browser

- Riwayat Aktivitas menampilkan aktor dan nama aksi, tetapi belum menampilkan payload old/new. Isi payload diverifikasi oleh automated test.
- Penyelesaian mentoring membutuhkan waktu jadwal yang sudah terlewati; gunakan selisih beberapa menit saat pengujian manual.
- LMS berada di luar scope produk.

## Checklist Lulus

- [ ] Level kompetensi memakai rubrik 1–5 dan tanggal penilaian masa depan ditolak.
- [ ] Target hanya menerima jabatan lebih tinggi dan dibatasi satu target aktif.
- [ ] Gap membedakan standar kosong, belum dinilai, dan level terkini.
- [ ] Kompetensi master yang sedang dipakai tidak dapat dihapus.
- [ ] Pengajuan pelatihan melewati Atasan dan HR sebelum disetujui.
- [ ] Pelatihan nonaktif atau kedaluwarsa tidak tersedia.
- [ ] Pelatihan dan mentoring tidak dapat diselesaikan sebelum waktunya.
- [ ] Rekomendasi Atasan tetap menunggu HR; penolakan HR dapat diajukan ulang.
- [ ] Pelatihan selesai tidak menaikkan level kompetensi otomatis.
- [ ] Mentoring menyimpan kompetensi, hasil, dan tindak lanjut.
- [ ] Create, update, dan delete kompetensi Pegawai muncul di audit.
