# Testing Web Merit System

[Kembali ke panduan utama](../../testing-web-sistem-sdm.md)

## 1. Smoke Test Istilah

### HR

1. Login sebagai HR.
2. Buka Kinerja > Periode Penilaian > Buat.
3. Pastikan form memakai label:
   - `Bobot kepatuhan dinas (%)`;
   - `Bobot umpan balik rekan (%)`;
   - `Dasar simulasi bonus`;
   - keterangan `Hanya simulasi; tidak terhubung ke payroll.`
4. Batalkan form; data periode akan dibuat pada skenario merit.

### Atasan dan Pegawai

1. Login sebagai Atasan. Pastikan menu memakai `Pengelolaan KPI`.
2. Login sebagai Pegawai. Pastikan menu memakai `Capaian KPI`.
3. Pastikan tidak ada istilah `Review 360`, `Disiplin`, atau `Estimasi Bonus` pada halaman merit.

Hasil lulus: istilah UI menjelaskan umpan balik rekan dan bonus simulasi; bukan review 360 penuh atau payroll.

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

### 2.5 Buktikan alur verifikasi dan publikasi

1. Sebagai Atasan, buka detail hasil. Aksi `Verifikasi Atasan` belum boleh terlihat karena periode belum selesai.
2. Login sebagai HR dan buka detail hasil. Aksi `Verifikasi dan Publikasikan` juga belum boleh terlihat.
3. Kembali ke Periode Penilaian dan edit `WEB Merit 01`.
4. Ubah Selesai menjadi kemarin. Mulai tetap dua hari lalu, lalu simpan.
5. Sebagai HR, pastikan periode dan indikator periode tersebut tidak dapat diedit atau dihapus.
6. Login sebagai Atasan. Pastikan KPI Pegawai periode tersebut tidak dapat diedit atau dihapus, lalu buka detail hasil dan klik `Verifikasi Atasan` > `Verifikasi Hasil`. Verifikasi wajib berhasil.
7. Login sebagai HR, buka detail hasil, lalu klik `Verifikasi dan Publikasikan`. Publikasi wajib ditolak karena empat pegawai aktif lain belum dihitung atau diverifikasi:

   ```text
   Publikasi menunggu 4 Pegawai yang belum dihitung atau diverifikasi Atasan.
   ```

8. Buka Organisasi > Pegawai. Nonaktifkan `Pegawai Demo 2` sampai `Pegawai Demo 5` karena tidak mengikuti finalisasi periode ini.
9. Kembali ke detail Hasil Merit dan jalankan `Verifikasi dan Publikasikan` lagi. Publikasi wajib berhasil.
10. Login sebagai Pegawai 1. Hasil sekarang wajib terlihat.
11. Login sebagai Atasan. Aksi `Rekomendasikan Pelatihan` sekarang wajib terlihat pada baris hasil.

## Checklist Lulus

- [ ] Semua istilah merit tampil sesuai konteks.
- [ ] KPI, penilaian Atasan, dan peer feedback yang kurang memblokir merit.
- [ ] Penilaian Pegawai kepada Atasan tidak dihitung sebagai peer feedback.
- [ ] Pegawai tanpa dinas selesai mendapat skor kepatuhan dinas `100`.
- [ ] Aksi verifikasi hanya tersedia setelah periode selesai.
- [ ] Publikasi menunggu seluruh Pegawai aktif dihitung dan diverifikasi Atasan.
- [ ] Periode, indikator, dan KPI Pegawai terkunci setelah periode selesai.
- [ ] Rekomendasi pelatihan baru tersedia setelah merit dipublikasikan.
