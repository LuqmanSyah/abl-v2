# Testing Web Pembinaan Karir

[Kembali ke panduan utama](../../testing-web-sistem-sdm.md)

## 1. Audit dan Gap Kompetensi

### 1.1 Siapkan target dan standar kompetensi

Login sebagai HR.

1. Buka Organisasi > Jabatan > Buat.
2. Isi Unit kerja `Operasional`, Nama jabatan `Direktur Web`, Level `9`, lalu simpan.
3. Buka Pengembangan > Kompetensi > Buat.
4. Buat kompetensi `Kepemimpinan Web`.
5. Buka Standar Kompetensi Jabatan > Buat.
6. Pilih Jabatan `Direktur Web`, Kompetensi `Kepemimpinan Web`, Level wajib `4`.

### 1.2 Uji create, update, dan tanggal masa depan

1. Buka Pengelolaan Kompetensi Pegawai > Buat.
2. Pilih Pegawai `Pegawai Demo 1`, Kompetensi `Kepemimpinan Web`, Level `2`, Tanggal penilaian hari ini, Catatan `Asesmen awal`.
3. Simpan.
4. Buka Laporan & Audit > Riwayat Aktivitas. Baris terbaru wajib berisi:
   - Pengguna: `Admin SDM`;
   - Aksi: `competency.created`.
5. Edit kompetensi pegawai tadi. Ubah Tanggal penilaian menjadi besok.
6. Simpan wajib ditolak dengan pesan:

   ```text
   Tanggal penilaian kompetensi tidak boleh di masa depan.
   ```

7. Ubah tanggal kembali ke hari ini, Level menjadi `3`, Catatan menjadi `Asesmen ulang`, lalu simpan.
8. Riwayat Aktivitas wajib memiliki `competency.updated` oleh `Admin SDM`.

### 1.3 Uji gap memakai nilai terbaru

1. Login sebagai Pegawai 1.
2. Buka Pengembangan > Rencana Karier > Buat.
3. Pilih Jabatan tujuan `Direktur Web`, lalu simpan.
4. Kolom Gap dan rekomendasi wajib menampilkan:

   ```text
   Kepemimpinan Web: 3/4 — Ajukan mentoring
   ```

5. Login sebagai HR. Ubah kembali Level kompetensi menjadi `2` dan simpan.
6. Login sebagai Pegawai 1 dan muat ulang Rencana Karier.
7. Gap wajib berubah menjadi `2/4`. Sistem memakai nilai terkini, bukan nilai lama.

### 1.4 Uji pelatihan tidak menaikkan level otomatis

1. Sebagai HR, kembalikan Level kompetensi ke `3`.
2. Buka Pengelolaan Pelatihan > Buat.
3. Buat pelatihan `Leadership Web`, Jenis `Internal`, Kompetensi terkait `Kepemimpinan Web`, Aktif ya.
4. Login sebagai Pegawai 1. Buka Pengajuan Pelatihan > Buat.
5. Pilih `Leadership Web`, isi alasan, lalu simpan.
6. Login sebagai Atasan. Buka Persetujuan Pelatihan dan klik `Setujui` > `Setujui Pengajuan`.
7. Login sebagai HR. Buka Verifikasi Pelatihan dan klik `Verifikasi HR` > `Verifikasi Pengajuan`.
8. Pada baris sama, klik `Catat Hasil`, isi hasil, lalu `Simpan Hasil`.
9. Login sebagai Pegawai 1. Buka Profil Kompetensi dan Rencana Karier.
10. Level wajib tetap `3`; angka gap wajib tetap `3/4`. Teks rekomendasi boleh berubah menjadi `Leadership Web` karena pelatihan aktif tersedia.

HR tetap harus melakukan asesmen ulang untuk menaikkan level.

### 1.5 Uji audit delete

1. Sebagai HR, hapus data `Kepemimpinan Web` milik Pegawai Demo 1 dari Pengelolaan Kompetensi Pegawai.
2. Buka Riwayat Aktivitas.
3. Baris terbaru wajib memiliki Aksi `competency.deleted` dan Pengguna `Admin SDM`.

## Batas Pengujian Browser

- Riwayat Aktivitas menampilkan aktor dan nama aksi, tetapi belum menampilkan payload old/new. Isi payload diverifikasi oleh automated test.
- LMS berada di luar scope produk.

## Checklist Lulus

- [ ] Create, update, dan delete kompetensi muncul di audit.
- [ ] Tanggal penilaian masa depan ditolak.
- [ ] Gap memakai level terbaru dan pelatihan selesai tidak menaikkan level otomatis.
