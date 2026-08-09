# Testing Web Absensi Dinas

[Kembali ke panduan utama](../../testing-web-sistem-sdm.md)

## 1. Smoke Test Istilah

1. Login sebagai HR. Pastikan menu Operasional memakai `Monitoring Dinas` dan `Monitoring Absensi Dinas`.
2. Login sebagai Atasan. Pastikan menu memakai `Pengelolaan Dinas` dan `Monitoring Absensi Dinas`.
3. Login sebagai Pegawai. Pastikan menu memakai `Pelaksanaan Dinas` dan `Riwayat Absensi Dinas`.

Hasil lulus: istilah UI menjelaskan dinas lapangan, bukan absensi umum.

## 2. Akurasi GPS Absensi Dinas

Nilai default `ATTENDANCE_MAX_ACCURACY_METERS` adalah `150`. Skenario memakai dua tugas karena satu tugas hanya menerima satu absensi per hari.

### 2.1 Buat dua tugas dinas

Login sebagai Atasan. Buka Operasional > Pengelolaan Dinas > Buat, lalu buat dua data berikut untuk `Pegawai Demo 1`.

Data bersama:

- Tanggal mulai dan selesai: hari ini;
- Nama lokasi: `Titik Uji Web`;
- Alamat: `Lokasi pengujian lokal`;
- Garis lintang: `-6.200000`;
- Garis bujur: `106.816666`;
- Batas jarak: `100` meter.

Bedakan Tujuan dinas menjadi `WEB GPS 150` dan `WEB GPS 151`. Data langsung berstatus `Ditugaskan`.

### 2.2 Uji batas 150 meter

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

### 2.3 Uji akurasi 151 meter

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

## Batas Pengujian Browser

- Halaman absensi selalu mengirim nilai akurasi dari browser. Kasus akurasi kosong tidak dapat dibuat dari alur UI normal; automated test menutup kasus ini.
- Face recognition berada di luar scope produk.

## Checklist Lulus

- [ ] Semua istilah absensi dinas tampil sesuai konteks.
- [ ] Akurasi `150` valid dan `151` memerlukan pemeriksaan.
