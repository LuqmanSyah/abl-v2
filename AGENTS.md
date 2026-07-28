## Project Stage and Local Data Policy

Project masih development. Data lokal bersifat disposable kecuali user menyatakan sebaliknya.

Agent boleh tanpa meminta konfirmasi:

- Menjalankan `php artisan migrate:fresh --seed`.
- Menghapus dan membuat ulang database testing/local.
- Mengubah, menggabungkan, atau menghapus migration lama.
- Menjalankan seeder, formatter, seluruh test, dan browser smoke test.
- Membersihkan cache Laravel.
- Membuat data dummy untuk verifikasi fitur.

Sebelum operasi destruktif database, agent wajib memastikan:

- `APP_ENV` adalah `local` atau `testing`.
- Database bukan production, staging, atau server remote.
- Nama database adalah database development project.
- Tidak ada data yang dinyatakan penting oleh user.

Agent dilarang tanpa instruksi eksplisit:

- Menjalankan operasi destruktif pada production/staging.
- Menghapus `.env`, upload pengguna, atau file di luar project.
- Menghapus perubahan Git milik user.
- Menjalankan `git reset --hard`, force push, atau mengubah history Git.
- Mengirim email/notifikasi nyata ke pengguna eksternal.
- Deploy ke production.

## Definition of Done

Perubahan selesai jika:

- Root cause diperbaiki, bukan gejala.
- Perubahan minimal dan sesuai pola project.
- `vendor/bin/pint --dirty` lulus.
- `php artisan test` lulus.
- Fitur utama diverifikasi lewat browser bila menyentuh UI atau alur pengguna.
- Tidak ada error pada `git diff --check`.
