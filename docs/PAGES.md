# Daftar Halaman Proyek

Dokumen ini mencatat halaman UI yang terdaftar pada implementasi saat ini. Endpoint aksi seperti login, logout, dan proses persetujuan tidak dihitung sebagai halaman. Aksi tambah atau edit berbentuk modal juga tidak memiliki route terpisah.

Role yang digunakan: `employee`, `manager`, `hr_admin`, `director`, dan `it_admin`.

## Halaman Umum

| Route | Halaman | Deskripsi | Akses |
| --- | --- | --- | --- |
| `/` | Beranda | Mengarahkan pengguna ke login atau panel yang sesuai dengan role. | Semua pengguna |
| `/login` | Login | Form autentikasi untuk masuk ke aplikasi. | Pengguna belum login |

## Portal Karyawan

Portal ini berada di `/app` dan dapat diakses oleh `employee` serta `manager`.

| Route | Halaman | Deskripsi |
| --- | --- | --- |
| `/app` | Dashboard Karyawan | Ringkasan presensi hari ini, tugas luar aktif, nilai merit terakhir, progres IDP, dan kesiapan karier. |
| `/app/attendances` | Presensi GPS | Riwayat check-in dan check-out milik pengguna. |
| `/app/attendances/create` | Rekam Presensi | Form check-in atau check-out dengan GPS, foto, dan alasan jika berada di luar radius. |
| `/app/attendance-requests` | Izin Tugas Luar | Daftar pengajuan tugas luar milik pengguna beserta statusnya. |
| `/app/attendance-requests/create` | Buat Izin Tugas Luar | Form pengajuan tugas luar atau WFH. |
| `/app/attendance-requests/{record}/edit` | Edit Izin Tugas Luar | Mengubah pengajuan milik pengguna yang masih dapat diedit. |
| `/app/leave-requests` | Pengajuan Cuti | Daftar pengajuan cuti milik pengguna beserta status persetujuannya. |
| `/app/leave-requests/create` | Buat Pengajuan Cuti | Form pengajuan jenis, tanggal, dan alasan cuti. |
| `/app/leave-requests/{record}/edit` | Edit Pengajuan Cuti | Mengubah pengajuan cuti milik pengguna yang masih berstatus pending. |
| `/app/performance-reviews` | Review Kinerja | Daftar review kinerja milik pengguna dan status prosesnya. |
| `/app/performance-reviews/{record}/edit` | Penilaian Diri | Mengisi nilai serta catatan diri pada review yang masih draft. |
| `/app/performance-reviews/create` | Buat Review Kinerja | Route terdaftar, tetapi pembuatan review dari portal karyawan ditolak oleh otorisasi. |
| `/app/individual-development-plans` | Rencana Pengembangan | Melihat rencana pengembangan pribadi dan memperbarui progresnya. |
| `/app/user-skills` | Keahlian | Melihat daftar keahlian dan level pengguna; bersifat baca saja. |

## Portal Admin

Portal ini berada di `/admin`. Isi navigasi dan data menyesuaikan role.

| Route | Halaman | Deskripsi | Akses |
| --- | --- | --- | --- |
| `/admin` | Dashboard Admin | Ringkasan persetujuan, kehadiran, kandidat promosi, dan distribusi merit sesuai role. | `manager`, `hr_admin`, `director`, `it_admin` |
| `/admin/attendances` | Presensi GPS | Memantau presensi; manager melihat bawahan langsung, HR melihat seluruh data dan dapat memutus pengecualian lokasi. | `manager`, `hr_admin` |
| `/admin/attendances/create` | Buat Presensi | Route terdaftar, tetapi pembuatan presensi dari portal admin ditolak oleh otorisasi. | Tidak ada |
| `/admin/daily-attendance-summaries` | Rekap Kehadiran | Rekap harian baca-saja dengan filter karyawan, status, departemen, dan rentang tanggal. | `hr_admin`, `director` |
| `/admin/attendance-requests` | Izin Tugas Luar | Memantau, membuat, menyetujui, atau menolak izin tugas luar sesuai cakupan role. | `manager`, `hr_admin` |
| `/admin/attendance-requests/create` | Buat Izin Tugas Luar | Form pembuatan izin tugas luar untuk karyawan. | `manager`, `hr_admin` |
| `/admin/attendance-requests/{record}/edit` | Edit Izin Tugas Luar | Mengubah detail pengajuan tugas luar. | `manager`, `hr_admin` |
| `/admin/leave-requests` | Pengajuan Cuti | Mengelola serta menyetujui atau menolak pengajuan cuti. | `hr_admin` |
| `/admin/leave-requests/create` | Buat Pengajuan Cuti | Form pembuatan pengajuan cuti untuk karyawan. | `hr_admin` |
| `/admin/leave-requests/{record}/edit` | Edit Pengajuan Cuti | Mengubah detail dan status pengajuan cuti. | `hr_admin` |
| `/admin/performance-reviews` | Review Kinerja | Mengelola review, penilaian KPI, submit, persetujuan, penguncian, dan hitung ulang merit. | `manager`, `hr_admin`, `director` |
| `/admin/performance-reviews/create` | Buat Review Kinerja | Membuat periode review untuk bawahan atau karyawan. | `manager`, `hr_admin` |
| `/admin/performance-reviews/{record}/edit` | Edit Review Kinerja | Mengisi penilaian KPI pada review draft sesuai kewenangan. | `manager`, `hr_admin` |
| `/admin/promotions` | Promosi | Manager mengusulkan promosi, HR memverifikasi, dan director memberi keputusan akhir. | `manager`, `hr_admin`, `director` |
| `/admin/branch-offices` | Kantor Cabang | Mengelola lokasi, koordinat GPS, dan radius presensi kantor. | `hr_admin` |
| `/admin/departments` | Departemen | Mengelola master departemen organisasi. | `hr_admin` |
| `/admin/positions` | Jabatan | Mengelola jabatan, level, departemen, dan persyaratan keahlian. | `hr_admin` |
| `/admin/work-schedules` | Jadwal Kerja | Mengelola hari kerja, jam masuk-pulang, dan toleransi keterlambatan. | `hr_admin` |
| `/admin/holidays` | Hari Libur | Mengelola kalender hari libur. | `hr_admin` |
| `/admin/skills` | Keahlian | Mengelola master keahlian dan kategorinya. | `hr_admin` |
| `/admin/user-skills` | Keahlian Karyawan | Mengelola keahlian serta level yang dimiliki setiap karyawan. | `hr_admin` |
| `/admin/kpis` | KPI | Mengelola indikator, kategori, dan bobot penilaian kinerja. | `hr_admin` |
| `/admin/career-paths` | Jalur Karier | Mengelola perpindahan dari jabatan saat ini ke jabatan tujuan. | `hr_admin` |
| `/admin/individual-development-plans` | Rencana Pengembangan | Mengelola target, periode, dan progres pengembangan karyawan. | `hr_admin` |
| `/admin/users` | Pengguna | Mengelola akun, role, atasan, kantor, dan posisi pengguna. | `it_admin` |

## Catatan Implementasi

- Halaman master data yang hanya memiliki route indeks memakai modal untuk tambah dan edit.
- Detail KPI review ditampilkan di form **Review Kinerja**, bukan sebagai halaman mandiri.
- Route dengan `{record}` memakai ID record yang dipilih.
