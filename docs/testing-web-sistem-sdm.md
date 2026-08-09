# Panduan Testing Web Sistem SDM

Panduan ini menguji implementasi aktif langsung melalui browser. Seeder hanya membuat akun dan master organisasi; data transaksi dibuat lewat UI dalam langkah berikut.

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

## Panduan per Layanan

| Layanan | Panduan | Cakupan |
| --- | --- | --- |
| Merit System | [Testing Web Merit System](modul/merit-system/testing-web.md) | Istilah UI, kelengkapan data, kalkulasi, verifikasi, dan publikasi merit |
| Absensi Dinas | [Testing Web Absensi Dinas](modul/absensi-dinas/testing-web.md) | Istilah UI dan batas akurasi GPS |
| Pembinaan Karir | [Testing Web Pembinaan Karir](modul/pembinaan-karir/testing-web.md) | Audit kompetensi, gap karier, dan pelatihan |

Setiap panduan memakai persiapan dan akun di atas. Seluruh layanan berada di luar scope payroll, absensi reguler, review 360 lengkap, face recognition, dan LMS.
