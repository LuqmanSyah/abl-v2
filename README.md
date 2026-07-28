# ABL — Sistem SDM

Aplikasi pengelolaan absensi dinas, KPI, merit, kompetensi, pelatihan, dan mentoring. Laravel serta Vite berjalan lokal; MySQL berjalan dalam Docker.

## Kebutuhan

- PHP 8.2+ dengan ekstensi `pdo_mysql`
- Composer
- Node.js dan npm
- Docker dengan Docker Compose

## Instalasi

```bash
composer setup
composer run dev
```

`composer setup` memasang dependency, membuat `.env` bila belum ada, menyalakan MySQL, menjalankan migration serta seeder master data dan akun bootstrap, lalu membangun aset frontend.

Buka `http://127.0.0.1:8000/login`.

## Akun Demo

Semua akun memakai kata sandi `password`.

- HR: `hr@example.com`
- Atasan: `atasan@example.com`
- Pegawai: `pegawai@example.com` sampai `pegawai5@example.com`

Seeder tidak membuat data transaksi.

## Database

Hanya MySQL yang berjalan dalam Docker. Aplikasi lokal terhubung melalui `127.0.0.1:3307`.

```bash
docker compose up -d --wait mysql
docker compose ps
docker compose down
```

Mengubah kredensial setelah volume dibuat tidak mengubah akun MySQL lama. Hapus volume hanya jika data lokal boleh hilang:

```bash
docker compose down -v
```

## Verifikasi

```bash
composer test
vendor/bin/pint --test
npm run build
```

Test memakai SQLite in-memory agar cepat dan terisolasi. Kompatibilitas MySQL diverifikasi melalui migration serta seeder master data dan akun bootstrap saat `composer setup`.

Panduan backup, restore, scheduler, dan deployment tersedia di [`docs/operations.md`](docs/operations.md).
