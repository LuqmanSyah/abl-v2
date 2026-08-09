# Absensi Dinas

Layanan pencatatan absensi berbasis foto dan lokasi GPS untuk Pegawai yang sedang menjalankan dinas.

## Alur

```text
Dinas berstatus Ditugaskan
  → Pegawai mengambil foto dan lokasi
  → Controller memvalidasi input
  → AttendanceRecorder memeriksa tugas, waktu, GPS, dan duplikasi harian
  → Status Valid, Terlambat, atau Memerlukan Pemeriksaan
  → HR memverifikasi data yang perlu diperiksa
  → Absensi Valid dipakai dalam skor kepatuhan dinas
```

## Komponen

| Layer | File | Fungsi |
| --- | --- | --- |
| Controller | `app/Http/Controllers/AttendanceController.php` | Menampilkan form, menyimpan absensi, dan menyajikan foto privat |
| Service | `app/Services/AttendanceRecorder.php` | Validasi bisnis dan pencatatan absensi |
| Model | `app/Models/Attendance.php` | Data absensi |
| Model | `app/Models/DutyTrip.php` | Data penugasan dinas |
| View | `resources/views/attendance/capture.blade.php` | Pengambilan foto dan GPS |

## Aturan Bisnis

- Hanya Pegawai yang ditugaskan dapat melakukan absensi.
- Dinas harus aktif dan sudah memasuki waktu mulai.
- Satu tugas menerima satu absensi per tanggal `captured_at`.
- Foto wajib berupa gambar dengan ukuran maksimal 5 MB.
- Jarak di luar radius, akurasi GPS di atas `ATTENDANCE_MAX_ACCURACY_METERS`, atau selisih jam perangkat di atas toleransi menghasilkan status `Memerlukan Pemeriksaan`.
- Absensi setelah jadwal berakhir berstatus `Terlambat`.
- Hanya absensi `Valid` dihitung dalam merit.

## Panduan Testing

- [Testing web](testing-web.md)
- [Testing dinas multi-hari](testing-multi-hari.md)

Automated test: `tests/Feature/DutyAttendanceTest.php`, `tests/Feature/DutyTripManagementTest.php`, dan `tests/Feature/FlowTest.php`.
