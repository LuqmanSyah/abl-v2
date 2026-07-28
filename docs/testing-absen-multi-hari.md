Bisa. Karena aplikasi berjalan di WSL, tanggal Windows dan WSL harus sama.

### Persiapan

1. Gunakan database lokal, bukan produksi.
2. Buat dinas multi-hari: hari ini sampai 2–3 hari berikutnya.
3. Login pegawai.
4. Lakukan absensi hari pertama sampai berhasil.

### Simulasikan hari berikutnya

1. Hentikan server Laravel dengan `Ctrl+C`.
2. Windows **Settings > Time & language > Date & time**.
3. Matikan **Set time automatically**.
4. Ubah tanggal menjadi besok. Jam tetap mendekati jam sekarang.
5. Buka PowerShell:

```powershell
wsl --shutdown
```

6. Buka ulang terminal WSL.
7. Pastikan waktu WSL benar:

```bash
date
```

8. Pastikan waktu Laravel sama:

```bash
php artisan tinker --execute="dump(now()->toDateTimeString());"
```

9. Jalankan ulang server:

```bash
php artisan serve
```

10. Refresh browser. Dinas harus muncul sebagai aktif dan status hari ini **Belum absen**.
11. Lakukan absensi lagi. Sistem membuat baris absensi kedua untuk tanggal baru.
12. Ulangi langkah sama untuk hari ketiga.

### Setelah selesai

1. Aktifkan kembali **Set time automatically**.
2. Tekan **Sync now**.
3. Jalankan lagi:

```powershell
wsl --shutdown
```

4. Buka WSL dan server kembali.

Selama tanggal palsu aktif, tutup aplikasi sinkronisasi seperti OneDrive. Semua timestamp lokal akan mengikuti tanggal palsu.
