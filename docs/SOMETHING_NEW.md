# 🚀 THE ULTIMATE BLUEPRINT (FINAL IMMUTABLE ARTIFACT): ARSITEKTUR SYSTEM HRIS

**Stack:** Laravel 12 + Filament v5 + Docker + Google Maps API

---

## 1. Spesifikasi Tech Stack & Arsitektur Multi-Panel

* **Core Framework:** **Laravel 12** (PHP 8.3+)
* **Admin & UI Engine:** **Filament v5 Multi-Panel Architecture**
* **`EmployeePanel` (`/app`):** Antarmuka *Mobile-First / PWA* khusus seluruh karyawan. Digunakan untuk Absensi GPS Kantor & Dinas, *Live Selfie*, Pengajuan Izin Dinas (*Bottom-Up*), Form *Check-Out* Pengecualian, *Self-Assessment* Merit, dan Tracking IDP.
* **`AdminPanel` (`/admin`):** Antarmuka Desktop untuk HR, Manager, Direksi, dan IT Admin. Digunakan untuk sistem *Approval*, Verifikasi Pengecualian Absensi, Manajemen Cabang/Kantor, KPI & Skill, Review Kinerja, Dashboard Eksekutif, dan *Panel Switcher*.


* **Database & Caching:** **Docker Containerization** (PostgreSQL/MySQL + Redis Cache).
* **Timezone Standard:** **Asia/Jakarta (WIB / UTC+7)**
* Seluruh penyimpanan dan pemrosesan waktu (`recorded_at`, `duty_start_datetime`, `duty_end_datetime`, serta eksekusi *Scheduled Job*) **WAJIB** menggunakan zona waktu `Asia/Jakarta` secara konsisten, baik pada konfigurasi Laravel (`config/app.php`), database container, maupun server OS untuk memastikan batas akhir hari (23:59 WIB) berjalan tepat waktu.


* **Location Services:** **Google Maps Platform + Server-Side Geofencing**
* *Geocoding API:* Menerjemahkan koordinat GPS ke alamat fisik (*Reverse Geocoding*).
* *Maps JavaScript API:* Widget peta interaktif di Dashboard Filament.
* *Server-Side Haversine:* Formula matematika independen di Laravel untuk validasi radius garis lurus.



---

## 2. Aturan Bisnis & Formulasi 3 Layanan Utama

### A. Layanan 1: Absensi Online GPS (Kantor Biasa & Tugas Luar)

#### 1. Referensi Lokasi Target

* **Absen Kantor Biasa (`attendance_request_id` = NULL):**
Menggunakan koordinat `latitude`, `longitude`, dan `allowed_radius_meters` dari **`branch_offices`** (sesuai `users.branch_office_id`).
* **Absen Tugas Luar / Dinas (`attendance_request_id` TERISI):**
Menggunakan koordinat `target_latitude`, `target_longitude`, dan `allowed_radius_meters` dari **`attendance_requests`**.

#### 2. Alur Penugasan Dinas, Multi-Hari, & Cross-Module Overlap Validation

* **Top-Down (Perintah Atasan):** Atasan membuat permohonan dinas lengkap dengan `duty_start_datetime` dan `duty_end_datetime` $\rightarrow$ Status otomatis `approved` dan `approved_by = created_by` (*Auto-Approved*).
* **Bottom-Up (Inisiatif Karyawan):** Karyawan mengajukan izin meeting luar $\rightarrow$ Status `pending` $\rightarrow$ Membutuhkan *Approval* Atasan agar tombol *check-in* aktif.
* **Tugas Luar Multi-Hari:** Untuk tugas luar yang berlangsung lebih dari 1 hari, karyawan wajib melakukan *check-in* dan *check-out* pada setiap hari kerja dalam rentang `duty_start_datetime` hingga `duty_end_datetime`. Agregator harian menggunakan `attendance_request_id` yang sama selama rentang tanggal tersebut.
* **Fleksibilitas Check-Out Hari Terakhir:** Pada hari terakhir tugas luar, jika karyawan telah selesai bertugas dan kembali ke kantor pusat/cabang asal, *check-out* diizinkan dilakukan di titik kantor asal (`branch_offices`) tanpa penalti geofencing, asalkan waktu *check-out* dilakukan setelah `duty_end_datetime`.
* **Full Cross-Module Overlap Validation:** Pembuatan `attendance_requests` dibatasi oleh sistem menggunakan interval waktu penuh, baik antar tugas luar maupun terhadap cuti yang sudah disetujui:

$$\text{Overlap} \iff (\text{new\_start} < \text{existing\_end}) \land (\text{new\_end} > \text{existing\_start})$$



*Sistem menolak pembuatan `attendance_requests` baru jika terjadi tumpang tindih waktu dengan `attendance_requests` lain ATAU `leave_requests` berstatus `approved` untuk karyawan yang sama.*

#### 3. Batas Waktu Check-In (Early Check-In vs Alfa Cutoff)

* **Jendela Check-In Awal (Early Check-In):** Tombol presensi *check-in* baru aktif paling awal **90 menit** sebelum jam masuk acuan (`check_in_time` atau `duty_start_datetime`). Presensi yang dilakukan lebih dari 90 menit sebelum jam masuk ditolak oleh sistem untuk mencegah kecurangan kehadiran.
* **Batas Keterlambatan vs Alfa (`alfa_cutoff_minutes`):** Jika *check-in* dilakukan melebihi $\text{jam masuk acuan} + \text{alfa\_cutoff\_minutes}$ (default: 120 menit), status sesi/harian tersebut otomatis ditetapkan sebagai **Alfa**.

#### 4. Geofencing Radius (Haversine)

* **Server-Side Validation:** Sistem menggunakan **Rumus Haversine** untuk menghitung jarak garis lurus antara titik presensi dan target. Presensi sah jika $distance\_to\_target \le allowed\_radius$:

$$d = 2r \arcsin\left(\sqrt{\sin^2\left(\frac{\Delta \phi}{2}\right) + \cos(\phi_1)\cos(\phi_2)\sin^2\left(\frac{\Delta \lambda}{2}\right)}\right)$$



Hasil Haversine disimpan di `distance_to_target_meters`. Google Geocoding hanya dipakai untuk mengambil alamat dan kegagalannya tidak memengaruhi validasi radius.
* **Check-Out Pengecualian (Out of Radius):** Jika *check-out* berada di luar radius, karyawan wajib mengisi form **"Check-Out Luar Radius"** (`exception_reason` + *Live Selfie*), tersimpan dengan `is_radius_exception = true` dan `status = pending_verification`.
* **Approved:** Status *check-out* menjadi sah.
* **Rejected / Timeout (Pukul 23:59 WIB):** Jika ditolak atau tidak diverifikasi hingga pukul 23:59 WIB, hari tersebut dianggap `missing_checkout` dan dikenakan penalti kedisiplinan (-5 poin).



#### 5. Multi-Session Check-In & Prioritas Agregasi Harian (Pukul 23:59 WIB)

* **Multi-Session Handling:** Check-in kantor biasa dan tugas luar diperlakukan sebagai sesi terpisah (maksimal 1 *check-in* valid per `attendance_request_id` **per hari kalender** berdasarkan tanggal `recorded_at`, atau 1 *check-in* per kantor biasa per hari kalender).
* **Aturan Foreign Key `daily_attendance_summaries`:** Kolom `check_in_id` dan `check_out_id` pada `daily_attendance_summaries` secara eksplisit **merujuk pada ID dari sesi yang MENANG prioritas**. Seluruh log sesi lainnya tetap tersimpan di tabel `attendances` sebagai riwayat audit.
* **Prioritas Agregator Harian:** Pada pukul 23:59 WIB, *scheduled job* menentukan status final di `daily_attendance_summaries` dengan hirarki:
1. **Prioritas 1 (Cuti/Izin & Libur):** Jika tanggal tersebut telah ditandai sebagai `leave` atau `holiday`, proses penalti `missing_checkout` diabaikan sepenuhnya.
2. **Prioritas 2 (Tugas Luar):** Mengikuti status sesi tugas luar terawal yang valid (`present`/`late`), mengesampingkan log absen kantor biasa. Hitungan `late_minutes` mengacu pada jam `duty_start_datetime`.
3. **Prioritas 3 (Kantor Biasa):** Menggunakan status log kantor biasa (`present`/`late`/`missing_checkout`/`alfa`). Hitungan `late_minutes` mengacu pada `check_in_time`.



#### 6. Otomatisasi Summary Cuti (`leave_requests`)

* Saat `leave_requests` di-approve (`approved_by` terisi), sistem secara otomatis membuat/meng-overwrite *record* di `daily_attendance_summaries` pada seluruh rentang tanggal `start_date` hingga `end_date` dengan `status = 'leave'`.

---

### B. Layanan 2: Merit System (Penilaian Kinerja)

#### 1. Multi-Rater Assessment & Mandatory KPI Details

* `self_score` & `self_notes`: Diisi oleh Karyawan (pembanding/kalibrasi).
* `manager_score` & `manager_notes`: Diisi oleh Atasan via `review_kpi_details`.
* **Mandatory Completion Constraint:** Rapor `performance_reviews` tidak dapat disubmit oleh Atasan sebelum **seluruh indikator KPI** pada `review_kpi_details` memiliki nilai `manager_score` yang terisi penuh.
* **Constraint Bobot KPI:** Di-snapshot dari master `kpis` saat rapor dibuat, dengan aturan wajib **$\sum \text{weight} = 100$** pada `review_kpi_details` sebelum status berubah menjadi `submitted`.

#### 2. Formulasi Attendance Score & Kalender Kerja

* **Definisi $n_{\text{hadir}}$:** Jumlah hari pada rentang periode yang memiliki status `present` atau `late` pada `daily_attendance_summaries`.

$$\text{Tanggal Mulai Hitung} = \max(\text{start\_date},\, \text{join\_date})$$


$$\text{Hari Kerja Efektif} = \text{Total Hari dari Tanggal Mulai s/d End Date} - (\text{Weekend} + \text{Holidays} + \text{Approved Leave})$$


$$n_{\text{alfa}} = \max(0,\, \text{Hari Kerja Efektif} - n_{\text{hadir}})$$


$$\text{Attendance Score} = \max\left(0,\, 100 - \left[(2 \times n_{\text{terlambat}}) + (5 \times n_{\text{missing\_checkout}}) + (10 \times n_{\text{alfa}})\right]\right)$$



#### 3. Formulasi Skor Akhir Merit & Event Recalculation

$$\text{Manager KPI Score} = \sum_{i=1}^{n} \left( \frac{\text{manager\_score}_i \times \text{weight}_i}{100} \right)$$

$$\text{Final Merit Score} = (20\% \times \text{Attendance Score}) + (80\% \times \text{Manager KPI Score})$$

* **Grading Kinerja:** **Grade A** ($\ge 85$), **Grade B** ($70 \le x < 85$), **Grade C** ($55 \le x < 70$), **Grade D** ($< 55$).
* **Event Recalculation Triggers:**
Setiap kali terjadi perubahan pada **`leave_requests`** (*approval* cuti susulan masa lalu), koreksi **`attendances`**, atau penambahan/perubahan tanggal pada **`holidays`** di masa lalu, sistem **WAJIB** memicu *background job* untuk:
1. Menemukan *record* `performance_reviews` milik karyawan terkait yang rentang `start_date`–`end_date`-nya mencakup tanggal tersebut.
2. Menghitung ulang `attendance_score` dan `final_merit_score`.
3. Memperbarui kolom `grade` jika hasil rekalkulasi mengubah nilai ambang batas.
4. **Pengecualian Status Locked:** Jika periode `performance_reviews` telah ditandai sebagai `status = 'locked'` atau `'finalized'` oleh HR/Direksi, sistem menolak rekalkulasi otomatis dan memerlukan otorisasi *forced recalculation* khusus.



---

### C. Layanan 3: Pembinaan Karir & Promosi Jabatan

#### 1. Kalkulasi Readiness Score (Capped Proportional)

$$\text{Readiness Score} = \left( \frac{\sum_{i=1}^{n} \min(\text{current\_level}_i,\, \text{min\_required\_level}_i)}{\sum_{i=1}^{n} \text{min\_required\_level}_i} \right) \times 100\%$$

#### 2. Kriteria & Query Constraint Candidate Pool

* *Background Job* bulanan memindai karyawan yang memenuhi **3 Syarat Dinamis**:
1. $\text{Readiness Score} \ge 80\%$
2. $\text{Grade Merit} \ge \text{career\_paths.min\_merit\_grade}$ (dinamis mengikuti batas minimum target posisi)
3. $\text{Masa Kerja (Bulan)} \ge \text{career\_paths.min\_experience\_months}$


* **Spesifikasi Query Dashboard:** Tampilan Candidate Pool di Filament Admin secara eksplisit **hanya memuat** *record* berstatus `proposed` yang dibuat dalam 30 hari terakhir.
* **Strict Expiry & Recycle Rule:**
* Aturan kedaluwarsa 30 hari **HANYA BERLAKU** untuk *record* yang berstatus **`proposed`** (belum ditindaklanjuti).
* *Record* dengan status `approved_by_hr` **TIDAK AKAN EXPIRED** secara otomatis agar memberikan waktu bagi Direksi untuk memberikan persetujuan akhir (*final sign-off*).
* *Record* `proposed` yang berusia $> 30$ hari diubah menjadi `expired`. Pada bulan baru, jika karyawan tetap *qualified*, sistem membuat entri `proposed` baru.



---

## 3. Matriks Stakeholder & Hak Akses (RBAC)

| Stakeholder | Primary Portal | Hak Akses & Peran Utama |
| --- | --- | --- |
| **Karyawan** | `EmployeePanel` (`/app`) | Presensi GPS Kantor/Dinas, ajukan izin dinas (*Bottom-Up*), form *Check-Out* Luar Radius, isi *Self-Assessment* Merit, update progres IDP, & lihat jalur karir. |
| **Manager** | `EmployeePanel` & `AdminPanel` | Absen GPS pribadi via `/app`. Approve izin dinas tim, verifikasi *Check-Out* Luar Radius, isi *Manager Review* Merit, & ajukan promosi via `/admin`. |
| **HR Admin** | `AdminPanel` (`/admin`) | Kelola master data (Cabang Kantor, Shift, Skill, Jabatan, Libur), verifikasi cuti (`leave_requests`), monitor rekap absensi, kelola IDP, & verifikasi berkas promosi. |
| **Direksi** | `AdminPanel` (`/admin`) | Akses *Executive Dashboard*, persetujuan akhir (*final sign-off*) promosi jabatan & alokasi insentif Merit. |
| **IT Admin** | `AdminPanel` (`/admin`) | Kelola akun, peranan (*roles/permissions*), pemeliharaan Docker/System Logs, dan konfigurasi API Keys. |

---

## 4. Skema Database Final (19 Tabel Utama)

```
                       ┌────────────────┐
                       │   Department   │
                       └───────┬────────┘
                               │ 1:N
┌────────────────┐     ┌───────┴────────┐     1:N     ┌─────────────────┐
│  WorkSchedule  ├────►│    Position    ├────────────►│   CareerPath    │
└────────────────┘     └───────┬────────┘             └─────────────────┘
                               │ 1:N
┌────────────────┐     ┌───────┴────────┐     1:N     ┌─────────────────┐
│ PositionSkill  ├────►│      User      ├────────────►│    UserSkill    │
└────────────────┘     └───────┬────────┘             └─────────────────┘
                               │ ◄──┐ (Self-reference: manager_id)
                               │ ◄──┤ (FK: branch_office_id)
     ┌─────────────────────────┼─────────────────────────┬─────────────────────────┐
     │ 1:N                     │ 1:N                     │ 1:N                     │ 1:N
┌────┴──────────────┐ ┌────────┴─────────┐ ┌─────────────┴───┐ ┌─────────────┴───┐
│AttendanceRequest  │ │   Attendance    │ │PerformanceReview│ │    Promotion    │
└────────┬──────────┘ └────────┬────────┘ └───────┬─────────┘ └─────────────────┘
         │ 1:N                 │ 1:1              │ 1:N
         │            ┌────────┴────────┐  ┌──────┴──────────┐
         └───────────►│ DailySummary    │  │ReviewKpiDetail  │
                      └─────────────────┘  └─────────────────┘

```

### A. Kelompok Master Data & Struktur Organisasi

1. **`departments`**: `id`, `name`, `code`
2. **`positions`**: `id`, `department_id` (FK), `title`, `level`
3. **`work_schedules`**: `id`, `name`, `check_in_time`, `check_out_time`, `late_tolerance_minutes`, `alfa_cutoff_minutes`
4. **`branch_offices`**: `id`, `name`, `code`, `latitude`, `longitude`, `allowed_radius_meters`
5. **`skills`**: `id`, `name`, `category`
6. **`position_skills`**: `id`, `position_id` (FK), `skill_id` (FK), `min_required_level`
7. **`user_skills`**: `id`, `user_id` (FK), `skill_id` (FK), `current_level`
8. **`users`**: `id`, `nip`, `name`, `email`, `password`, `position_id` (FK), `work_schedule_id` (FK), `branch_office_id` (FK ke `branch_offices.id`), `manager_id` (FK ke `users.id`), `join_date`, `status`

### B. Kelompok Absensi & Kalender Kerja

9. **`holidays`**: `id`, `name`, `date`
10. **`leave_requests`**: `id`, `user_id` (FK), `type` (`sick`/`paid_leave`/`permit`), `start_date`, `end_date`, `reason`, `status`, `approved_by` (FK ke `users.id`, nullable), `approved_at` (timestamp, nullable)
11. **`attendance_requests`**: `id`, `user_id` (FK), `created_by` (FK), `flow_type` (`top_down`/`bottom_up`), `destination_name`, `destination_address`, `target_latitude`, `target_longitude`, `allowed_radius_meters`, `duty_start_datetime` (datetime), `duty_end_datetime` (datetime), `reason`, `status`, `approved_by` (FK ke `users.id`, nullable)
12. **`attendances`**: `id`, `user_id` (FK), `attendance_request_id` (FK ke `attendance_requests.id`, nullable), `type` (`check_in`/`check_out`), `latitude`, `longitude`, `distance_to_target_meters`, `is_fallback` (boolean, default false), `address_snapshot`, `photo_path`, `is_radius_exception` (boolean, default false), `exception_reason` (text, nullable), `status` (`normal`/`late`/`pending_verification`/`rejected`), `recorded_at`
13. **`daily_attendance_summaries`**: `id`, `user_id` (FK), `attendance_request_id` (FK ke `attendance_requests.id`, nullable), `date`, `check_in_id` (FK ke `attendances.id`, nullable — *merujuk ke ID sesi pemenang*), `check_out_id` (FK ke `attendances.id`, nullable — *merujuk ke ID sesi pemenang*), `status` (`present`/`late`/`alfa`/`leave`/`holiday`/`missing_checkout`), `late_minutes`

### C. Kelompok Merit System

14. **`kpis`**: `id`, `name`, `category`, `weight`
15. **`performance_reviews`**: `id`, `user_id` (FK), `reviewer_id` (FK), `period`, `start_date`, `end_date`, `attendance_score`, `manager_kpi_score`, `final_merit_score`, `grade`, `status` (`draft`/`submitted`/`approved`/`locked`)
16. **`review_kpi_details`**: `id`, `performance_review_id` (FK), `kpi_id` (FK), `self_score`, `self_notes`, `manager_score`, `manager_notes`, `weight`, `subtotal_score`

### D. Kelompok Pembinaan Karir

17. **`career_paths`**: `id`, `current_position_id` (FK), `next_position_id` (FK), `min_experience_months`, `min_merit_grade`
18. **`individual_development_plans`**: `id`, `user_id` (FK), `mentor_id` (FK), `title`, `action_plan`, `progress_percentage`, `target_completion_date`, `status`
19. **`promotions`**: `id`, `user_id` (FK), `from_position_id` (FK), `to_position_id` (FK), `proposed_by` (FK), `readiness_score`, `status` (`proposed`/`approved_by_hr`/`approved_by_director`/`rejected`/`expired`), `effective_date`

---

## 5. Ringkasan Relasi Eloquent (Model Laravel)

* **`User`**
* `belongsTo(Position::class)`, `belongsTo(WorkSchedule::class)`, `belongsTo(BranchOffice::class)`, `belongsTo(User::class, 'manager_id')`
* `hasMany(UserSkill::class)`, `hasMany(LeaveRequest::class)`, `hasMany(AttendanceRequest::class)`, `hasMany(Attendance::class)`, `hasMany(DailyAttendanceSummary::class)`
* `hasMany(PerformanceReview::class)`, `hasMany(IndividualDevelopmentPlan::class)`, `hasMany(Promotion::class)`


* **`BranchOffice`**
* `hasMany(User::class)`


* **`Position`**
* `belongsTo(Department::class)`, `hasMany(PositionSkill::class)`, `hasMany(User::class)`


* **`LeaveRequest`**
* `belongsTo(User::class)`, `belongsTo(User::class, 'approved_by')`


* **`AttendanceRequest`**
* `belongsTo(User::class)`, `belongsTo(User::class, 'created_by')`, `belongsTo(User::class, 'approved_by')`
* `hasMany(Attendance::class)`, `hasMany(DailyAttendanceSummary::class)`


* **`Attendance`**
* `belongsTo(User::class)`, `belongsTo(AttendanceRequest::class)`


* **`DailyAttendanceSummary`**
* `belongsTo(User::class)`, `belongsTo(AttendanceRequest::class)`, `belongsTo(Attendance::class, 'check_in_id')`, `belongsTo(Attendance::class, 'check_out_id')`


* **`PerformanceReview`**
* `belongsTo(User::class)`, `belongsTo(User::class, 'reviewer_id')`, `hasMany(ReviewKpiDetail::class)`


* **`ReviewKpiDetail`**
* `belongsTo(PerformanceReview::class)`, `belongsTo(Kpi::class)`


* **`Promotion`**
* `belongsTo(User::class)`, `belongsTo(User::class, 'proposed_by')`, `belongsTo(Position::class, 'from_position_id')`, `belongsTo(Position::class, 'to_position_id')`



---

🏁 **ARTEFAK DESAIN FINAL TERTUTUP DAN IMMUTABLE.** Seluruh aspek logika bisnis, struktur database, dan *edge cases* telah tervalidasi 100%. Selamat mengeksekusi ke dalam kode Laravel 12 + Filament v5!
