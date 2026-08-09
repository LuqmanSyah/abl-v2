# BAB VII

# PENUTUP

## 7.1 Kesimpulan

Berdasarkan implementasi dan pengujian saat laporan diperbarui, diperoleh kesimpulan berikut.

1. Sistem SDM berhasil mengintegrasikan data organisasi, perjalanan dinas, absensi, KPI, merit, kompetensi, target karier, pelatihan, mentoring, laporan, dan audit dalam satu aplikasi web.
2. Arsitektur aktual adalah modular monolith berbasis Laravel dengan application service layer. Tiga panel Filament melayani Pegawai, Atasan, dan HR menggunakan autentikasi session, satu basis data, dan satu unit deployment.
3. Absensi dinas menggabungkan jadwal penugasan, GPS, akurasi, radius Haversine, pemeriksaan waktu perangkat, foto kamera, watermark, idempotensi, dan pemeriksaan HR. Foto berfungsi sebagai bukti visual privat, bukan verifikasi biometrik.
4. Sistem merit menghitung KPI, kepatuhan dinas, penilaian Atasan, dan umpan balik rekan menggunakan bobot per periode yang totalnya wajib 100%. Setelah periode berakhir, hasil diverifikasi Atasan; aksi HR kemudian memverifikasi sekaligus mempublikasikannya setelah seluruh Pegawai aktif siap. Nilai bonus hanya berupa simulasi, bukan pembayaran riil.
5. Pembinaan karier menghubungkan standar kompetensi jabatan, kompetensi Pegawai, target jabatan, analisis gap, pelatihan, rekomendasi berbasis merit, dan mentoring melalui workflow yang dibatasi peran serta status.
6. Laporan HR menyatukan data lintas modul dan dapat diekspor ke CSV, XLSX, serta PDF. Sistem juga menyediakan database notification, email tertentu, activity log, command terjadwal, dan dukungan backup.
7. Keamanan dasar diterapkan melalui autentikasi session, pembatasan panel dan record, middleware akun aktif, CSRF, rate limit, validasi input, password hashing, private storage, transaction, row lock, dan perlindungan formula spreadsheet.
8. Pengujian otomatis pada 9 Agustus 2026 menghasilkan **105 test lulus dengan 603 assertion**. Hasil ini memvalidasi alur unit, HTTP, model, service, database, Livewire, ekspor, dan backup yang tercakup, tetapi tidak menggantikan pengujian kamera/GPS pada perangkat fisik, browser penuh, infrastruktur production, atau load test.

Dengan demikian, tujuan utama proyek telah tercapai pada batas as-built yang dijelaskan. Laporan tidak memasukkan SOA terdistribusi, REST API independen, pengenalan wajah, sinkronisasi luring, WebSocket, Web Push, WhatsApp, login sosial, payroll, atau multi-tenant sebagai fitur aktif karena komponen tersebut tidak terdapat pada implementasi saat ini.

## 7.2 Saran

Pengembangan berikutnya sebaiknya dilakukan berdasarkan kebutuhan operasional yang telah terbukti, dengan prioritas sebagai berikut.

1. Menjalankan dan mendokumentasikan pengujian manual pada perangkat target untuk kamera, GPS, izin browser, Google Maps, responsive layout, serta kondisi jaringan buruk.
2. Menetapkan kebijakan organisasi untuk toleransi waktu, batas akurasi GPS, absensi terlambat, tugas multi-hari, akhir pekan, dan perlakuan setiap status terhadap skor kepatuhan.
3. Menetapkan retensi dan prosedur penghapusan foto, koordinat, komentar penilaian, serta data sensitif lain sebelum deployment production.
4. Menyiapkan HTTPS, queue worker, cron scheduler, mail transport, monitoring, rotasi log, backup MySQL, dan uji pemulihan sebagai bagian runbook deployment.
5. Menambah browser automation hanya untuk alur yang sering mengalami regresi dan dapat dimock secara stabil, terutama login, panel peran, serta laporan.
6. Melakukan load test setelah tersedia target jumlah pengguna serentak dan service-level objective yang terukur.
7. Mempertimbangkan deteksi mock location, dukungan luring, atau aplikasi mobile native hanya bila hasil pengujian lapangan menunjukkan kebutuhan yang nyata.
8. Menambahkan REST API atau memecah modul menjadi layanan terpisah hanya jika muncul konsumen eksternal, kebutuhan deployment independen, atau batas skala yang tidak lagi dapat ditangani modular monolith.
9. Mengintegrasikan payroll hanya setelah formula bonus, otorisasi pembayaran, rekonsiliasi, audit keuangan, dan tanggung jawab hukum ditetapkan secara formal.
10. Meninjau berkala kesesuaian dokumentasi, diagram, screenshot, dan angka pengujian terhadap build aktif agar laporan tidak kembali memuat fitur atau hasil yang sudah usang.
