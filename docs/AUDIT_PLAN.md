Ya. Mulai code audit. Sebelum membaca kode, jadikan `SOMETHING_NEW.md` baseline aturan.

## Putaran 1: Audit read-only

1. **Petakan spesifikasi**
   - Aktor.
   - Panel.
   - Aksi.
   - Status awal.
   - Penyetuju.
   - Hak edit.
   - Notifikasi.
   - Efek ke modul lain.
   - Tandai aturan ambigu, termasuk kewenangan HR.

2. **Inventaris kode**
   - Semua Filament resource.
   - Model event.
   - Policy/authorization.
   - Query scope.
   - Notification.
   - Service.
   - Scheduled job.
   - Test.

3. **Audit authorization**
   - Employee hanya data sendiri.
   - Manager hanya bawahan.
   - HR sesuai kewenangan.
   - Director/IT sesuai batas.
   - Coba akses URL dan record langsung.
   - Cek `user_id`, `created_by`, status, approve, edit, delete.

4. **Audit workflow**
   - Tugas luar dan cuti.
   - Presensi dan pengecualian lokasi.
   - Review dan merit.
   - Promosi.
   - IDP dan skill.
   - Master data.
   - Dashboard, notifikasi, scheduled job.

5. **Audit test gap**
   - Cocokkan setiap aturan dengan test.
   - Cari test yang hanya menguji model, tetapi tidak menguji panel.
   - Cari alur tanpa simulasi dua akun.
   - Catat regression test yang wajib dibuat.

## Hasil putaran 1

Buat `docs/CODE_AUDIT.md`. Setiap temuan berisi:

```text
AUD-001
Severity: Critical
Fitur: Izin Tugas Luar
Spesifikasi: Top-down dibuat atasan
Masalah: Admin dapat membuat bottom-up
Dampak: Karyawan bisa mengubah penugasan
Lokasi: file + baris
Reproduksi: langkah singkat
Perbaikan: usulan minimum
Test hilang: skenario lintas akun
```

Severity:

- `Critical`: akses atau perubahan data tanpa hak.
- `High`: workflow, status, notifikasi, perhitungan salah.
- `Medium`: default/form/UI menyesatkan.
- `Low`: label, konsistensi, maintainability.

## Putaran 2: Perbaikan

Urutan:

1. `Critical`
2. `High`
3. Tambah regression test
4. Jalankan seluruh test
5. `Medium`
6. `Low` bila masih relevan

## Putaran 3: UAT

Setelah code audit dan perbaikan:

- HR membuat data.
- Manager memproses.
- Budi menerima hasil.
- Coba aksi sah dan terlarang.
- Cocokkan hasil dengan dokumen.

Target selesai: seluruh fitur punya matriks role-workflow, seluruh temuan punya bukti, seluruh bug penting punya regression test.
