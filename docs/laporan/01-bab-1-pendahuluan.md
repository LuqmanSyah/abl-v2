# BAB I

# PENDAHULUAN

## 1.1 Latar Belakang

Pengelolaan sumber daya manusia membutuhkan data yang akurat, dapat ditelusuri, dan tersedia bagi pihak yang tepat. Pada proses manual atau aplikasi yang terpisah, data penugasan, kehadiran, kinerja, dan pengembangan pegawai sering tidak memiliki hubungan yang jelas. Kondisi tersebut menyulitkan organisasi saat memeriksa keabsahan kehadiran dinas, mengevaluasi kinerja, dan menentukan tindak lanjut pengembangan karier.

Absensi dinas memiliki kebutuhan yang berbeda dari absensi harian di kantor. Pegawai bekerja pada lokasi dan waktu yang ditentukan melalui suatu perintah dinas. Pencatatan kehadiran berbasis waktu saja belum cukup karena tidak membuktikan kedekatan pegawai dengan lokasi tugas. Bukti foto tanpa koordinat juga masih memerlukan pemeriksaan manual yang besar. Oleh karena itu, pencatatan absensi dinas perlu menggabungkan jadwal tugas, koordinat GPS, akurasi pembacaan lokasi, jarak dari titik tugas, waktu pengambilan, dan foto sebagai satu rekam data.

Penilaian kinerja juga membutuhkan dasar yang lebih terukur. Nilai akhir yang hanya berasal dari pendapat satu pihak berisiko tidak menggambarkan capaian pegawai secara menyeluruh. Sistem perlu menggabungkan capaian indikator kinerja utama, kepatuhan terhadap tugas dinas, penilaian Atasan, dan umpan balik rekan. Bobot setiap komponen perlu dapat ditetapkan per periode, sedangkan hasilnya perlu melewati verifikasi sebelum dipublikasikan kepada pegawai.

Pengembangan karier seharusnya mengikuti kesenjangan kompetensi nyata. Pegawai perlu mengetahui perbedaan antara kompetensi yang dimiliki dan standar jabatan tujuan. Atasan serta HR kemudian dapat memakai informasi tersebut untuk memberikan rekomendasi pelatihan atau mentoring yang relevan. Tanpa hubungan antara data kompetensi, hasil merit, pelatihan, dan mentoring, pembinaan karier cenderung bersifat umum dan sulit dievaluasi.

Berdasarkan kebutuhan tersebut, dikembangkan **Sistem SDM** berbasis web. Sistem ini mengintegrasikan tiga kelompok modul utama:

1. **Perjalanan dinas dan absensi**, berupa pengelolaan perintah dinas dan pencatatan kehadiran berbasis GPS serta foto;
2. **KPI dan merit**, berupa sistem penilaian kinerja dan simulasi bonus berdasarkan hasil penilaian;
3. **Pengembangan karier**, berupa pemetaan kompetensi, target karier, pelatihan, dan mentoring.

Implementasi saat ini menggunakan satu aplikasi Laravel dengan tiga panel berbasis peran: Pegawai, Atasan, dan Admin SDM/HR. Seluruh modul berjalan pada satu basis data dan satu unit deployment. Logika bisnis utama dipisahkan ke dalam service class agar tanggung jawab setiap modul tetap terstruktur. Bentuk ini merupakan **modular monolith dengan application service layer**, bukan kumpulan layanan independen atau REST API yang dapat di-deploy terpisah.

## 1.2 Identifikasi Masalah

Masalah yang menjadi dasar pengembangan sistem adalah sebagai berikut.

Kelima masalah dikelompokkan berdasarkan area proses SDM yang ditangani, yaitu validasi kehadiran dinas, penilaian kinerja dan merit, pembinaan karier, pengendalian alur persetujuan, serta keterhubungan data antarfungsi. Pengelompokan ini disusun agar setiap masalah dapat ditelusuri ke tujuan khusus dan rancangan modul yang bersesuaian pada Bab IV.

### 1.2.1 Validasi Kehadiran Dinas Belum Memadai

Pencatatan kehadiran tanpa hubungan dengan perintah dinas, lokasi, dan waktu tugas tidak cukup untuk memastikan bahwa pegawai hadir pada tempat yang ditentukan. Sistem memerlukan pemeriksaan radius, akurasi GPS, perbedaan waktu perangkat dan server, serta bukti foto yang hanya dapat diakses pengguna berwenang.

Apabila kehadiran tidak dapat dipertanggungjawabkan secara objektif, perintah dinas berisiko disalahgunakan dan hasil evaluasi kinerja menjadi tidak adil bagi pegawai lain. Validasi yang terlalu longgar juga memperbesar beban pemeriksaan manual HR sehingga kesalahan dapat luput dari pengawasan. Oleh karena itu, absensi dinas menuntut rekam data gabungan yang memungkinkan setiap kehadiran ditelusuri sejak pengambilan bukti hingga penetapan status.

### 1.2.2 Penilaian Kinerja Belum Terukur dan Terpadu

Data KPI, kepatuhan dinas, penilaian Atasan, dan umpan balik rekan dapat tersebar pada media berbeda. Akibatnya, perhitungan nilai sulit direproduksi dan keputusan tidak mempunyai jejak audit yang memadai.

Perbedaan sumber data menyebabkan dua pihak dapat memperoleh hasil yang berbeda untuk proses yang sama, sehingga objektivitas penilaian dipertanyakan. Tanpa jejak audit, pegawai juga kesulitan memahami dasar nilai yang diterimanya. Sistem perlu menyatukan seluruh komponen penilaian pada satu periode yang jelas dengan formula dan tahapan yang dapat diverifikasi.

### 1.2.3 Pembinaan Karier Belum Berbasis Kesenjangan Kompetensi

Pegawai belum memperoleh gambaran terstruktur mengenai kompetensi yang harus ditingkatkan untuk mencapai jabatan tujuan. Pada praktik saat ini, usulan pelatihan cenderung muncul tanpa perbandingan sistematis antara kompetensi aktual dan standar jabatan, sehingga pelatihan dan mentoring berisiko diberikan tanpa hubungan langsung dengan kebutuhan kompetensi.

Pengembangan yang tidak berbasis data cenderung bersifat umum, sulit dievaluasi, dan kurang sejalan dengan kebutuhan jabatan. Sistem perlu membandingkan level kompetensi aktual dengan standar jabatan tujuan sehingga rekomendasi pengembangan dapat diarahkan pada pelatihan atau mentoring yang paling relevan.

### 1.2.4 Persetujuan dan Publikasi Belum Terkendali

Pengajuan pelatihan, mentoring, verifikasi merit, dan pemeriksaan absensi melibatkan beberapa peran. Tanpa aturan status dan pembatasan aktor, data dapat berubah pada tahap yang tidak semestinya atau disetujui oleh pihak yang tidak berwenang.

Perubahan status yang tidak terkendali dapat menghasilkan keputusan yang tidak sah dan menyulitkan pelacakan tanggung jawab. Oleh karena itu, setiap alur yang melibatkan lebih dari satu aktor perlu dimodelkan sebagai proses berstatus dengan pemeriksaan peran, kepemilikan, dan waktu.

### 1.2.5 Data Antarfungsi SDM Belum Terhubung

Keputusan HR membutuhkan pandangan terpadu terhadap organisasi, absensi dinas, merit, pelatihan, dan mentoring. Setiap fungsi saat itu mengelola data pada media terpisah, sehingga riwayat aktivitas kepegawaian tersebar dan tidak dapat dirangkai menjadi satu sumber riwayat yang utuh. Data yang terpisah tersebut menghambat penyusunan laporan dan pelacakan riwayat aktivitas.

Keterpisahan data juga menyulitkan penyusunan kebijakan karena informasi yang diperlukan tersebar di beberapa tempat. Sistem perlu menjaga hubungan data lintas fungsi dan mencatat aktivitas penting sehingga laporan dan audit dapat disusun dari satu sumber yang utuh.

## 1.3 Rumusan Masalah

Rumusan masalah proyek ini adalah sebagai berikut.

1. Bagaimana membangun sistem web terintegrasi untuk mengelola organisasi, perintah dinas, absensi dinas, merit, dan pengembangan karier dalam satu aplikasi?
2. Bagaimana memvalidasi absensi dinas menggunakan jadwal tugas, GPS, akurasi lokasi, radius geofencing, waktu perangkat, dan foto tanpa mengklaim verifikasi biometrik?
3. Bagaimana menghitung merit berdasarkan KPI, kepatuhan dinas, penilaian Atasan, dan umpan balik rekan dengan bobot yang dapat ditetapkan per periode?
4. Bagaimana menghubungkan standar jabatan, kompetensi pegawai, target karier, pelatihan, serta mentoring untuk menghasilkan rekomendasi pengembangan?
5. Bagaimana membatasi panel, data, aksi, foto, persetujuan, dan publikasi sesuai peran Pegawai, Atasan, serta HR?
6. Bagaimana menyediakan laporan, ekspor, notifikasi, audit aktivitas, dan backup yang mendukung operasi sistem?

## 1.4 Tujuan Proyek

### 1.4.1 Tujuan Umum

Membangun Sistem SDM yang mengintegrasikan pengelolaan absensi dinas, sistem merit, dan pembinaan karier dalam satu aplikasi berbasis web.

Pemilihan wujud satu aplikasi bukan sekadar penggabungan tampilan, melainkan diarahkan pada satu basis data dan satu unit deployment untuk seluruh fungsi. Dengan cara ini, alur yang memotong beberapa fungsi, seperti absensi dinas yang menjadi bahan penilaian, hasil merit yang menjadi masukan rekomendasi pelatihan, serta data kompetensi yang menjadi dasar target karier, dapat saling terhubung tanpa perantara pemindahan data manual. Tujuan umum ini sekaligus menjawab masalah keterpisahan data antarfungsi yang diuraikan pada bagian 1.2.5.

### 1.4.2 Tujuan Khusus

1. Menyediakan autentikasi terpusat dan panel sesuai peran pengguna.
2. Menyediakan pengelolaan unit, jabatan, akun, serta hubungan Atasan–Pegawai.
3. Menyediakan pembuatan dan pemantauan perintah dinas berdasarkan bawahan langsung.
4. Mencatat absensi dinas menggunakan lokasi GPS, akurasi, foto kamera, watermark, waktu, dan validasi radius.
5. Menyediakan pemeriksaan HR untuk absensi dengan data lokasi atau waktu yang meragukan.
6. Mengelola periode penilaian, indikator KPI, capaian pegawai, penilaian kinerja, serta hasil merit.
7. Menyediakan verifikasi hasil merit oleh Atasan dan HR sebelum publikasi.
8. Menampilkan simulasi bonus berdasarkan skor merit tanpa memproses pembayaran.
9. Menganalisis kesenjangan kompetensi terhadap jabatan tujuan.
10. Mendukung alur pengajuan, rekomendasi, persetujuan, penolakan, dan penyelesaian pelatihan serta mentoring.
11. Menyediakan laporan HR, ekspor CSV/XLSX/PDF, notifikasi, audit aktivitas, scheduler, dan backup.
12. Memastikan aturan bisnis penting terlindungi oleh validasi, transaksi basis data, pembatasan akses, dan pengujian otomatis.

## 1.5 Manfaat Proyek

### 1.5.1 Manfaat bagi Pegawai

Pegawai memperoleh satu portal untuk melihat tugas dinas, melakukan absensi, memantau KPI dan merit yang telah dipublikasikan, melihat profil kompetensi, menentukan target karier, mengajukan pelatihan, dan mengikuti mentoring. Dengan satu portal tersebut, pegawai tidak perlu berpindah aplikasi untuk mengelola seluruh aktivitas kepegawaian. Transparansi hasil penilaian dan rekomendasi pengembangan memberi pegawai gambaran yang jelas mengenai posisi dan peluang kenaikan kariernya.

Keberhasilan manfaat ini dapat diukur dari kemampuan pegawai melacak status pengajuan dan hasil penilaian secara mandiri, tanpa harus menanyakan ulang kepada Atasan atau HR, serta memperoleh rekomendasi pengembangan yang tertaut langsung pada kebutuhan kompetensinya.

### 1.5.2 Manfaat bagi Atasan

Atasan dapat mengelola penugasan bawahan langsung, memantau absensi, mencatat KPI, memberikan penilaian, memverifikasi merit, menindaklanjuti pengajuan pelatihan, merekomendasikan pelatihan berdasarkan hasil merit, dan mengelola mentoring. Kemudahan tersebut menempatkan seluruh proses pembinaan dalam satu alur yang terpantau. Keputusan penugasan dan pengembangan bawahan menjadi lebih cepat karena didukung data yang tersedia pada panel Atasan.

Keberhasilan manfaat ini ditandai dengan perpindahan pekerjaan penilaian dan verifikasi dari berkas manual ke panel terpusat, serta kemampuan Atasan menelusuri dasar setiap penilaian yang diberikannya tanpa mencari data lintas aplikasi.

### 1.5.3 Manfaat bagi HR

HR memperoleh pengelolaan data organisasi dan master, pemeriksaan absensi, pengaturan periode merit, verifikasi serta publikasi hasil, pemeliharaan kompetensi dan katalog pelatihan, laporan lintas modul, ekspor, audit aktivitas, dan dukungan backup. Laporan dan audit yang terpusat mengurangi pekerjaan rekonsiliasi manual. Pemeriksaan absensi yang terintegrasi dengan bukti lokasi dan foto memperkuat dasar keputusan administrasi kepegawaian.

Keberhasilan manfaat ini diukur dari kemampuan HR menyusun laporan lintas fungsi dari satu basis data, melacak jejak aktivitas penting (audit), serta memeriksa kehadiran dengan bukti lokasi dan foto tanpa bergantung pada media terpisah.

### 1.5.4 Manfaat Akademis

Proyek menjadi contoh penerapan modular monolith pada aplikasi Laravel yang menggabungkan antarmuka berbasis peran, transaksi untuk workflow, geofencing, perhitungan merit, analisis kompetensi, dan pengujian integrasi. Laporan ini mendokumentasikan kompromi desain antara kebutuhan fungsional dan batasan implementasi nyata. Hasil pengujian otomatis turut menjadi rujukan praktik pengujian fitur pada aplikasi berbasis Filament dan Livewire.

Manfaat akademis bagi pengembang maupun peneliti berikutnya adalah memperoleh gambaran nyata tentang keputusan pemilihan satu basis data dan satu unit deployment bagi kebutuhan yang selama ini digabungkan secara manual, berikut titik-titik penetapan status pada absensi dinas dan merit yang perlu dijaga oleh transaksi basis data agar hasil tetap dapat diverifikasi.

## 1.6 Batasan Proyek

Batasan sistem berdasarkan implementasi aktif adalah sebagai berikut.

1. Sistem berbentuk aplikasi web untuk satu organisasi dan belum mendukung multi-tenant.
2. Pengguna dibatasi pada tiga peran: Pegawai, Atasan, dan Admin SDM/HR.
3. Absensi yang dikelola adalah absensi perjalanan dinas, bukan presensi harian seluruh pegawai.
4. Absensi memerlukan koneksi internet saat data dikirim. Tidak ada antrean luring, IndexedDB, atau service worker untuk sinkronisasi absensi.
5. Foto digunakan sebagai bukti visual privat. Sistem tidak melakukan pengenalan wajah, pencocokan biometrik, atau deteksi identitas.
6. Sistem memakai Browser Geolocation API dan tidak mendeteksi pemalsuan lokasi pada tingkat sistem operasi perangkat.
7. Google Maps digunakan untuk pencarian dan pemilihan lokasi dinas; ketersediaannya bergantung pada konfigurasi API key dan layanan eksternal Google.
8. Status absensi aktif adalah `Valid`, `Terlambat`, dan `Memerlukan Pemeriksaan`. Lokasi di luar radius menghasilkan status pemeriksaan, bukan status khusus `OutsideRadius`.
9. Penilaian merit memakai KPI, kepatuhan dinas, penilaian Atasan, dan umpan balik rekan. Penilaian Pegawai kepada Atasan disimpan sebagai umpan balik, tetapi tidak menjadi skor rekan bagi Pegawai.
10. Nilai bonus bersifat simulasi berdasarkan dasar bonus periode dan tidak terhubung ke payroll atau transaksi pembayaran.
11. Notifikasi aktif menggunakan database dan email pada kejadian tertentu. Sistem tidak menyediakan Web Push, WhatsApp, WebSocket, atau preferensi kanal per pengguna.
12. Antarmuka menggunakan autentikasi session. Tidak ada REST API independen, Laravel Sanctum, atau login sosial melalui Laravel Socialite.
13. Penyimpanan utama ditujukan untuk MySQL, sedangkan pengujian otomatis memakai SQLite in-memory.
14. Pengujian otomatis mencakup level unit, HTTP, model, service, dan Livewire. Otomasi browser penuh serta pengujian kamera/GPS pada perangkat fisik belum menjadi bagian suite otomatis.

## 1.7 Sistematika Penulisan

Laporan disusun dalam tujuh bab dengan sistematika sebagai berikut.

- **Bab I Pendahuluan** menjelaskan latar belakang, identifikasi masalah, rumusan masalah, tujuan, manfaat, batasan proyek, dan sistematika penulisan.
- **Bab II Metodologi** menguraikan pendekatan arsitektur modular monolith, metode pengembangan RAD, serta perangkat dan teknologi yang digunakan.
- **Bab III Tinjauan Pustaka** membahas kajian sistem sejenis dan landasan teori yang mendasari perancangan sistem.
- **Bab IV Analisis dan Perancangan Sistem** menyajikan kebutuhan fungsional dan nonfungsional, arsitektur, pemodelan sistem, perancangan basis data, serta perancangan UI/UX.
- **Bab V Implementasi Sistem** menjelaskan struktur implementasi, penerapan setiap modul, dan tangkapan layar.
- **Bab VI Hasil dan Pembahasan** menampilkan hasil pengujian, verifikasi formula, pembahasan, dan keterbatasan hasil.
- **Bab VII Penutup** memuat kesimpulan dan saran pengembangan berikutnya.

Bagian akhir laporan memuat daftar pustaka dan lampiran pendukung.

---
