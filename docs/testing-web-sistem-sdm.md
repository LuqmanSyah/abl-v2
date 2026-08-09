# Panduan Testing Web Sistem SDM

Panduan ini menguji perubahan pada `rencana-perubahan-sistem-sdm.md` langsung melalui browser. Seeder hanya membuat akun dan master organisasi; data transaksi dibuat lewat UI dalam langkah berikut.

## Persiapan

Untuk instalasi pertama:

```bash
composer setup
composer run dev
```

Buka `http://127.0.0.1:8000/login`.

Untuk mengulang seluruh skenario dari data kosong, pastikan `.env` memakai `APP_ENV=local`, MySQL lokal di `127.0.0.1:3307`, dan bukan database penting. Lalu jalankan:

```bash
php artisan migrate:fresh --seed
```

Perintah tersebut menghapus seluruh data lokal.

Gunakan logout atau browser profile berbeda saat berpindah peran.

| Peran | Email | Password | Panel |
| --- | --- | --- | --- |
| HR | `hr@example.com` | `password` | `/hr` |
| Atasan | `atasan@example.com` | `password` | `/atasan` |
| Pegawai 1 | `pegawai@example.com` | `password` | `/pegawai` |
| Pegawai 2 | `pegawai2@example.com` | `password` | `/pegawai` |

## 1. Smoke Test Istilah

### HR

1. Login sebagai HR.
2. Pastikan menu Operasional memakai `Monitoring Dinas` dan `Monitoring Absensi Dinas`.
3. Buka Kinerja > Periode Penilaian > Buat.
4. Pastikan form memakai label:
   - `Bobot kepatuhan dinas (%)`;
   - `Bobot umpan balik rekan (%)`;
   - `Dasar simulasi bonus`;
   - keterangan `Hanya simulasi; tidak terhubung ke payroll.`
5. Batalkan form; data periode akan dibuat pada skenario merit.

### Atasan dan Pegawai

1. Login sebagai Atasan. Pastikan menu memakai `Pengelolaan Dinas`, `Monitoring Absensi Dinas`, dan `Pengelolaan KPI`.
2. Login sebagai Pegawai. Pastikan menu memakai `Pelaksanaan Dinas`, `Riwayat Absensi Dinas`, dan `Capaian KPI`.
3. Pastikan tidak ada istilah `Review 360`, `Disiplin`, atau `Estimasi Bonus` pada halaman merit.

Hasil lulus: istilah UI menjelaskan dinas lapangan, umpan balik rekan, dan bonus simulasi; bukan absensi umum, review 360 penuh, atau payroll.

## 2. Merit dan Publikasi

### 2.1 Buat periode dan indikator

Login sebagai HR.

1. Buka Kinerja > Periode Penilaian > Buat.
2. Isi:
   - Nama periode: `WEB Merit 01`;
   - Mulai: dua hari lalu;
   - Selesai: besok;
   - Bobot KPI: `40`;
   - Bobot kepatuhan dinas: `20`;
   - Bobot penilaian Atasan: `20`;
   - Bobot umpan balik rekan: `20`;
   - Dasar simulasi bonus: `1000000`;
   - Aktif: ya.
3. Simpan. Total bobot harus tepat `100`.
4. Sebelum membuat indikator atau KPI Pegawai, jalankan `Hitung Merit`. Aksi wajib ditolak dengan pesan:

   ```text
   Data merit belum tersedia: belum ada KPI Pegawai pada periode ini.
   ```

5. Buka Kinerja > Indikator KPI > Buat.
6. Buat indikator pertama:
   - Periode: `WEB Merit 01`;
   - Indikator: `Kualitas Web`;
   - Satuan: `persen`;
   - Bobot: `50`.
7. Buat indikator kedua dengan periode sama:
   - Indikator: `Ketepatan Web`;
   - Satuan: `persen`;
   - Bobot: `50`.

### 2.2 Buktikan data wajib tidak dianggap nol

Login sebagai Atasan.

1. Buka Kinerja > Pengelolaan KPI > Buat.
2. Isi:
   - Periode: `WEB Merit 01`;
   - Indikator KPI: `Kualitas Web`;
   - Pegawai: `Pegawai Demo 1`;
   - Target: `100`;
   - Capaian: `80`.
3. Simpan. Jangan buat KPI kedua dahulu.

Login sebagai HR.

1. Buka Periode Penilaian.
2. Pada `WEB Merit 01`, jalankan `Hitung Merit` dan konfirmasi.
3. Hasil wajib ditolak dengan pesan:

   ```text
   Data merit belum lengkap: KPI Pegawai, penilaian Atasan, umpan balik Rekan.
   ```

Login sebagai Atasan, lalu buat KPI kedua untuk Pegawai Demo 1:

- Indikator KPI: `Ketepatan Web`;
- Target: `100`;
- Capaian: `80`.

Login sebagai HR dan jalankan `Hitung Merit` lagi. Hasil wajib ditolak dengan pesan:

```text
Data merit belum lengkap: penilaian Atasan, umpan balik Rekan.
```

### 2.3 Buktikan penilaian bawahan bukan umpan balik rekan

Login sebagai Atasan.

1. Buka Kinerja > Umpan Balik Kinerja > Buat.
2. Pilih periode `WEB Merit 01` dan Pegawai `Pegawai Demo 1`.
3. Isi Nilai `4`, komentar bebas, lalu simpan.

Login sebagai Pegawai 1.

1. Buka Kinerja > Umpan Balik Kinerja > Buat.
2. Pilih periode `WEB Merit 01` dan `Atasan Demo` sebagai pegawai yang dinilai.
3. Isi Nilai `5`, komentar bebas, lalu simpan.

Login sebagai HR dan jalankan `Hitung Merit`. Penilaian Pegawai 1 kepada Atasan tidak boleh dianggap peer review. Hasil wajib tetap ditolak:

```text
Data merit belum lengkap: umpan balik Rekan.
```

Login sebagai Pegawai 2.

1. Buka Kinerja > Umpan Balik Kinerja > Buat.
2. Pilih periode `WEB Merit 01` dan `Pegawai Demo 1`.
3. Isi Nilai `4`, komentar bebas, lalu simpan.

Login sebagai HR. Jalankan `Hitung Merit` lagi. Hasil wajib berhasil.

### 2.4 Periksa hasil dan skor kepatuhan dinas

1. Sebagai HR, buka Kinerja > Hasil Merit.
2. Buka hasil `WEB Merit 01` untuk `Pegawai Demo 1`.
3. Periksa:
   - Nilai KPI: `80`;
   - Nilai kepatuhan dinas: `100` karena belum ada dinas selesai pada periode;
   - Nilai Atasan: `80`;
   - Nilai umpan balik rekan: `80`;
   - Skor merit: `84`;
   - Simulasi bonus: `Rp840.000`.
4. Login sebagai Atasan. Buat dinas `WEB Dinas Tanpa Absensi` untuk Pegawai Demo 1 dengan tanggal mulai dan selesai kemarin. Isi lokasi manual apa pun yang valid, lalu simpan. Jangan buat absensi untuk dinas ini.
5. Login sebagai HR dan jalankan `Hitung Merit` kembali.
6. Buka hasil yang sama. Nilai kepatuhan dinas wajib turun menjadi `0`, Skor merit menjadi `64`, dan Simulasi bonus menjadi `Rp640.000`.
7. Login sebagai Pegawai 1. Hasil tersebut belum boleh terlihat karena belum dipublikasikan.
8. Login sebagai Atasan. Pada Hasil Merit, aksi `Rekomendasikan Pelatihan` belum boleh terlihat.

### 2.5 Buktikan publikasi sebelum periode selesai ditolak

1. Sebagai Atasan, buka detail hasil dan klik `Verifikasi Atasan` > `Verifikasi Hasil`.
2. Login sebagai HR, buka detail hasil, lalu klik `Verifikasi dan Publikasikan`.
3. Publikasi wajib ditolak dengan pesan:

   ```text
   Hasil merit hanya dapat dipublikasikan setelah periode selesai.
   ```

4. Sebagai HR, kembali ke Periode Penilaian dan edit `WEB Merit 01`.
5. Ubah Selesai menjadi kemarin. Mulai tetap dua hari lalu.
6. Kembali ke detail Hasil Merit dan jalankan `Verifikasi dan Publikasikan` lagi.
7. Publikasi wajib berhasil dan hasil terkunci.
8. Login sebagai Pegawai 1. Hasil sekarang wajib terlihat.
9. Login sebagai Atasan. Aksi `Rekomendasikan Pelatihan` sekarang wajib terlihat pada baris hasil.

## 3. Akurasi GPS Absensi Dinas

Nilai default `ATTENDANCE_MAX_ACCURACY_METERS` adalah `150`. Skenario memakai dua tugas karena satu tugas hanya menerima satu absensi per hari.

### 3.1 Buat dua tugas dinas

Login sebagai Atasan. Buka Operasional > Pengelolaan Dinas > Buat, lalu buat dua data berikut untuk `Pegawai Demo 1`.

Data bersama:

- Tanggal mulai dan selesai: hari ini;
- Nama lokasi: `Titik Uji Web`;
- Alamat: `Lokasi pengujian lokal`;
- Garis lintang: `-6.200000`;
- Garis bujur: `106.816666`;
- Batas jarak: `100` meter.

Bedakan Tujuan dinas menjadi `WEB GPS 150` dan `WEB GPS 151`. Data langsung berstatus `Ditugaskan`.

### 3.2 Uji batas 150 meter

Login sebagai Pegawai 1.

1. Buka Pelaksanaan Dinas, lalu detail `WEB GPS 150`.
2. Klik `Lakukan Absensi Dinas`.
3. Klik `Buka kamera`, izinkan kamera, ambil foto.
4. Buka DevTools > Console dan jalankan:

   ```js
   navigator.geolocation.getCurrentPosition = (ok) => ok({
       coords: { latitude: -6.2, longitude: 106.816666, accuracy: 150 },
   });
   ```

5. Klik `Ambil lokasi dan simpan absensi dinas`.
6. Pesan wajib `Absensi dinas berhasil disimpan.`

### 3.3 Uji akurasi 151 meter

1. Buka detail `WEB GPS 151` dan ulangi pengambilan foto.
2. Di Console, jalankan snippet sama dengan `accuracy: 151`.
3. Kirim absensi.
4. Pesan wajib menyebut status `Memerlukan Pemeriksaan`.
5. Login sebagai HR, buka Monitoring Absensi Dinas, lalu buka kedua data.
6. Periksa:
   - `WEB GPS 150`: Status `Valid`, Akurasi GPS `150`;
   - `WEB GPS 151`: Status `Memerlukan Pemeriksaan`, Akurasi GPS `151`;
   - alasan data kedua: `Akurasi GPS tidak tersedia atau melewati batas.`

Jika browser melarang override geolocation, gunakan DevTools Sensors atau perangkat dengan GPS. Nilai batas yang presisi tetap diverifikasi oleh automated test.

## 4. Audit dan Gap Kompetensi

### 4.1 Siapkan target dan standar kompetensi

Login sebagai HR.

1. Buka Organisasi > Jabatan > Buat.
2. Isi Unit kerja `Operasional`, Nama jabatan `Direktur Web`, Level `9`, lalu simpan.
3. Buka Pengembangan > Kompetensi > Buat.
4. Buat kompetensi `Kepemimpinan Web`.
5. Buka Standar Kompetensi Jabatan > Buat.
6. Pilih Jabatan `Direktur Web`, Kompetensi `Kepemimpinan Web`, Level wajib `4`.

### 4.2 Uji create, update, dan tanggal masa depan

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

### 4.3 Uji gap memakai nilai terbaru

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

### 4.4 Uji pelatihan tidak menaikkan level otomatis

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

### 4.5 Uji audit delete

1. Sebagai HR, hapus data `Kepemimpinan Web` milik Pegawai Demo 1 dari Pengelolaan Kompetensi Pegawai.
2. Buka Riwayat Aktivitas.
3. Baris terbaru wajib memiliki Aksi `competency.deleted` dan Pengguna `Admin SDM`.

## Batas Pengujian Browser

- Halaman absensi selalu mengirim nilai akurasi dari browser. Kasus akurasi kosong tidak dapat dibuat dari alur UI normal; automated test menutup kasus ini.
- Riwayat Aktivitas menampilkan aktor dan nama aksi, tetapi belum menampilkan payload old/new. Isi payload diverifikasi oleh automated test.
- Panduan ini tidak menguji payroll, absensi reguler, review 360 lengkap, face recognition, atau LMS karena semuanya di luar scope produk.

## Checklist Lulus

- [ ] Semua istilah baru tampil sesuai konteks.
- [ ] KPI, penilaian Atasan, dan peer feedback yang kurang memblokir merit.
- [ ] Penilaian Pegawai kepada Atasan tidak dihitung sebagai peer feedback.
- [ ] Pegawai tanpa dinas selesai mendapat skor kepatuhan dinas `100`.
- [ ] Merit tidak dapat dipublikasikan sebelum periode selesai.
- [ ] Rekomendasi pelatihan baru tersedia setelah merit dipublikasikan.
- [ ] Akurasi `150` valid dan `151` memerlukan pemeriksaan.
- [ ] Create, update, dan delete kompetensi muncul di audit.
- [ ] Tanggal penilaian masa depan ditolak.
- [ ] Gap memakai level terbaru dan pelatihan selesai tidak menaikkan level otomatis.
