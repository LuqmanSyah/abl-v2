# Ponytail Audit — Post-Implementation Blueprint HRIS v3

Tanggal: 2026-07-26  
Baseline: `ddcf2a0` (`feat: complete HRIS blueprint implementation`)  
Cakupan: seluruh tree saat ini, dengan `SOMETHING_NEW.md` dan `EXECUTION_PLAN.md` sebagai batas kebutuhan.  
Batas audit: kompleksitas dan over-engineering saja; correctness, security, performance, serta kelengkapan blueprint tidak dinilai. Estimasi baris tidak menghitung lockfile, aset vendor/generated, atau file laporan ini.
Status: dieksekusi; Tailwind/Vite build, formatter, route discovery, dependency validation, dan 50 test lulus.

Audit lama di `docs/PONYTAIL_AUDIT.md` menggambarkan kondisi sebelum cleanup pada commit baseline, sehingga tidak dipakai sebagai bukti kondisi saat ini.

## Findings

1. `delete:` 250 baris artefak UI tidak punya referensi runtime: stylesheet Filament tidak pernah dimuat, `map-picker` tidak pernah dirender, dan komponen logo tidak pernah dipanggil. Replacement: nothing. [`public/css/portal-filament.css`, `resources/views/filament/forms/components/map-picker.blade.php`, `resources/views/components/brand-logo.blade.php`]
2. `shrink:` axios, face-api, dan entrypoint JS tidak punya consumer runtime. Tailwind/Vite dipertahankan sesuai keputusan pemilik dan kini menjadi shared Filament theme lewat `viteTheme`; build hanya memproses CSS. Replacement: hapus JS mati beserta dua dependency-nya. [`resources/js/app.js`, `resources/js/bootstrap.js`, `resources/css/app.css`, `vite.config.js`, `package.json`]
3. `shrink:` setup organisasi 23 baris pada tes login tidak memengaruhi autentikasi atau redirect. Replacement: reuse `DatabaseSeeder`, lalu ambil employee demo. [`tests/Feature/ExampleTest.php`]
4. `delete:` no-op framework boilerplate: base controller kosong dan `AppServiceProvider::register()` kosong. Replacement: controller konkret tanpa base lokal dan inherited `ServiceProvider::register()`; binding `withExceptions()` wajib Laravel dipertahankan sebagai satu baris. [`app/Http/Controllers/Controller.php`, `app/Providers/AppServiceProvider.php`, `bootstrap/app.php`]
5. `delete:` factory state `unverified()` tidak punya caller dan blueprint tidak mengaktifkan email verification. Replacement: nothing; tambah kembali bersama fitur verifikasi bila dibutuhkan. [`database/factories/UserFactory.php`]
6. `native:` allowlist tujuh resource employee mengulang daftar `EmployeePanelProvider::resources()`; resource yang tidak terdaftar sudah tidak dapat diakses lewat panel. Replacement: pertahankan pemeriksaan role saja dan gunakan registrasi native Filament sebagai allowlist. [`app/Filament/Resources/RoleAwareResource.php`]
7. `delete:` dua override `getRelations()` hanya mengembalikan array kosong identik dengan default Filament. Replacement: inherited implementation. [`app/Filament/Resources/AttendanceRequestResource.php`, `app/Filament/Resources/LeaveRequestResource.php`]
8. `delete:` tiga flag `config/hr.php` tidak pernah dibaca aplikasi dan tidak tercantum dalam dua dokumen target. Replacement: nothing; konfigurasi lahir bersama consumer, bukan sebelumnya. [`config/hr.php`, `.env.example`]
9. `delete:` `laravel/sail` tidak dipakai; setup, README, dan operasi memakai `docker compose` langsung. Replacement: Docker Compose yang sudah ada. [`composer.json`]

Service skor, `GoogleMapsService`, event/listener recalculation, `BusinessRuleException`, notification base, command terjadwal, dan file Page Filament tidak ditandai: semuanya diwajibkan plan atau punya beberapa consumer/framework convention yang nyata.

net: -335 lines, -3 direct dependencies applied.
