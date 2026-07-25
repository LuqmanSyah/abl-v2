# Konteks Proyek — Sistem Manajemen SDM (ABL)

---

## 1. Gambaran Proyek

**Nama:** ABL — Sistem Sumber Daya Manusia
**Tujuan:** Sistem terintegrasi untuk absensi dinas berbasis GPS, sistem merit/KPI, dan pembinaan karir pegawai.
**Target pengguna:** 3 peran — Pegawai, Atasan, Admin SDM/HR.

**Stack teknologi:**
- Laravel 12 + Filament 5 (monolith)
- PHP 8.2+, MySQL 8.4 (Docker), SQLite (testing)
- Tailwind CSS 4, Vite 7
- Frontend capture page: vanilla JS + IndexedDB (offline queue)

---

## 2. Aktor & Panel

| Peran | Role enum | Panel path | Fungsi utama |
|-------|-----------|------------|--------------|
| Pegawai | `Employee` | `/pegawai` | Lihat dinas, absensi GPS+foto, KPI, merit, karir, training, mentoring |
| Atasan | `Manager` | `/atasan` | Buat perintah dinas, set KPI, review 360, approve training/mentoring |
| Admin SDM/HR | `Hr` | `/hr` | Kelola organisasi, merit system, kompetensi, laporan |

**Satu login page** (`/login`), redirect otomatis ke panel sesuai role.

---

## 3. Modul — Ringkasan & Status

| # | Modul | Status | BRD Ref | Fase |
|---|-------|--------|---------|------|
| 1 | Organisasi (unit, jabatan, user) | ✅ Selesai | FR-USR-01–03 | Fase 0 |
| 2 | Absensi Dinas (perintah, GPS, foto, offline) | ✅ Selesai | FR-ABS-01–13 | Fase 1 |
| 3 | KPI & Merit System (periode, KPI, 360, kalkulasi) | ✅ Selesai | FR-MRT-01–08 | Fase 2 |
| 4 | Pembinaan Karir (kompetensi, gap, training, mentoring) | ✅ Selesai | FR-KAR-01–10 | Fase 3 |
| 5 | Laporan & Operasional (dashboard, CSV, audit, backup) | ✅ Selesai | NFR-02,04,05,09 | Fase 4 |
| 6 | **Revisi: Rekomendasi Training oleh Atasan** | ✅ Selesai | — | Fase 5 |

---

## 4. Arsitektur Data

### 4.1. Model Utama

```
Organisasi         → Unit → Position → User (role, manager_id)
                   
Operasional       → DutyLocation → DutyTrip (employee_id, manager_id) → Attendance
                   
Merit             → ReviewPeriod → KpiIndicator → EmployeeKpi
                  → PerformanceReview (3 types)
                  → MeritResult

Karir             → Competency → PositionCompetency → EmployeeCompetency
                  → CareerGoal
                  → Training → TrainingRequest
                  → Mentoring

Sistem            → ActivityLog (morphTo subject)
```

### 4.2. Relasi Kunci

```
User (Employee) ──manager_id──→ User (Manager)
User ──unit_id──→ Unit
User ──position_id──→ Position

DutyTrip ──employee_id──→ User
DutyTrip ──manager_id──→ User
DutyTrip ──duty_location_id──→ DutyLocation
Attendance ──duty_trip_id──→ DutyTrip
Attendance ──employee_id──→ User

MeritResult ──review_period_id──→ ReviewPeriod
MeritResult ──employee_id──→ User
EmployeeKpi ──kpi_indicator_id──→ KpiIndicator
EmployeeKpi ──employee_id──→ User

PerformanceReview ──reviewer_id──→ User
PerformanceReview ──reviewee_id──→ User

CareerGoal ──user_id──→ User
CareerGoal ──target_position_id──→ Position

TrainingRequest ──user_id──→ User
TrainingRequest ──training_id──→ Training
TrainingRequest ──manager_id──→ User

Mentoring ──employee_id──→ User
Mentoring ──manager_id──→ User
```

### 4.3. Enums

| Enum | Values |
|------|--------|
| `UserRole` | `Employee`, `Manager`, `Hr` |
| `DutyTripStatus` | `Pending`, `Approved`, `Rejected`, `Completed`, `Cancelled` |
| `AttendanceStatus` | `Valid`, `OutsideRadius`, `Late`, `PendingSync`, `NeedsReview` |
| `ReviewType` | `ManagerToEmployee`, `EmployeeToManager`, `Peer` |
| `TrainingRequestStatus` | `PendingManager`, `Rejected`, `PendingHr`, `Approved`, `Completed` |
| `MentoringStatus` | `Pending`, `Approved`, `Rejected`, `Completed` |

---

## 5. Alur Bisnis Utama

### 5.1. Perintah Dinas + Absensi

```
Atasan buat DutyTrip (pilih bawahan + lokasi via map)
  → Status Approved
    → Pegawai lihat di panel
      → Buka halaman /pegawai/dinas/{id}/absensi
        → GPS + foto kamera langsung + watermark
          → Dalam radius & tepat waktu → Valid ✅
          → Di luar radius → OutsideRadius ⚠️
          → Terlambat → Late ⚠️
          → GPS mencurigakan / clock mismatch → NeedsReview 🔍
            → Trip otomatis Completed
              → Atasan/HR monitor hasil
```

**Offline:** IndexedDB simpan queue → auto-sync saat online.

### 5.2. Merit System

```
HR buat ReviewPeriod bulanan + KpiIndicator
  → Atasan set EmployeeKpi (target)
    → Pegawai jalankan
      → Atasan update achievement
        → Review 360 (atasan→pegawai, pegawai→atasan, peer)
          → HR jalankan MeritCalculator::calculate() sebagai draft bulanan
            → kpi_score + discipline_score + manager_score + 360_score = total_score
              → Jika belum diverifikasi, HR dapat hitung ulang dan `calculated_at` berubah
              → Atasan verifyByManager()
                → HR verifyByHr() + publish
                  → Pegawai lihat hasil merit + estimasi bonus
```

### 5.3. Pembinaan Karir

```
HR set PositionCompetency (standar kompetensi per jabatan)
  → Pegawai pilih CareerGoal (jabatan tujuan lebih tinggi)
    → Sistem hitung gap kompetensi
      → Rekomendasi: training atau mentoring
        → Pegawai ajukan TrainingRequest / Mentoring
          → Atasan approve → HR verify (training) / Atasan jadwalkan (mentoring)
            → Complete
```

### 5.4. Rekomendasi Training oleh Atasan (Revisi)

```
Manager lihat merit pegawai (manual decide)
  → Manager create TrainingRequest untuk employee
    → Status langsung Approved (skip manager approval)
      → Employee lihat di panel
        → HR complete setelah selesai
```

Detail implementasi: `docs/revisi.md`.

---

## 6. Rumus Merit

```
total_score = (
    kpi_score × kpi_weight
  + discipline_score × discipline_weight
  + manager_score × manager_weight
  + review_360_score × review_360_weight
) / 100
```

| Komponen | Sumber data | Range | Default bobot |
|----------|-------------|-------|---------------|
| KPI Score | `employee_kpis.achievement/target × weight`, cap 120% | 0-120 | 40% |
| Discipline | `valid_days / total_calendar_days × 100`, cap 100 | 0-100 | 20% |
| Manager | `avg(performance_reviews.type=manager_to_employee) / 5 × 100` | 0-100 | 20% |
| 360 | `avg(performance_reviews.type=employee_to_manager OR peer) / 5 × 100` | 0-100 | 20% |

**Bobot wajib total 100%** (dijaga oleh model `ReviewPeriod::booted()`).

**Update bulanan:** `MeritResult.calculated_at` mencatat waktu hitung/update terakhir. Hitung ulang hanya boleh sebelum verifikasi Atasan.

**Verifikasi 2-tahap:** Atasan verify → HR verify + publish → employee bisa lihat.

---

## 7. Keamanan & Aturan

### 7.1. Akses
- 3 panel Filament, masing-masing hanya untuk 1 role
- `User::canAccessPanel()` cek `role` + `is_active`
- `EnsureUserIsActive` middleware logout inactive user
- Scope data via `scopeVisibleTo()` per model (Employee: sendiri, Manager: bawahan, HR: semua)

### 7.2. Aturan Bisnis (DomainException)
- Position harus dari unit yang sama dengan user
- Manager dengan bawahan tidak bisa dinonaktifkan/diubah role
- DutyTrip employee harus bawahan langsung manager
- Attendance hanya untuk trip Approved, dalam radius, sesuai jadwal
- KPI target > 0, achievement >= 0
- Merit yang sudah diverifikasi/published → hasil tidak dapat dihitung ulang; data published terkunci
- CareerGoal target position level > current position level
- Mentoring requested_at tidak boleh lampau
- Score review 1-5 (tanpa validasi model — bug B3)

### 7.3. State Machine Locking
- `TrainingRequest` → `transition()` dengan `DB::transaction` + `lockForUpdate()` ✅
- `Mentoring` → tanpa locking ❌ (bug B2)
- `MeritResult.verifyByManager/verifyByHr` → `DB::transaction` + `lockForUpdate()` ✅
- `AttendanceRecorder.record` → `DB::transaction` + `lockForUpdate()` ✅

---

## 8. Bug Ditemukan (dari QA)

| ID | Bug | Severity | Area |
|----|-----|----------|------|
| B1 | `canAttend()` izinkan Completed, `record()` tolak (**Fixed**) | **Critical** | Attendance |
| B2 | Mentoring approve/reject tanpa locking | **Critical** | Mentoring |
| B3 | Score 0-255 tanpa validasi max 5 | **High** | Performance Review |
| B4 | Trip selalu Completed meski absensi flagged (**Fixed**) | **High** | Attendance |
| B10 | `guardManager()` baca memory, bukan DB fresh | **High** | Mentoring |
| B5 | `reviewScore()` falsy 0.0 vs null | **Medium** | Merit |
| B6 | `captured_at` masa depan valid | **Medium** | Attendance |
| B7 | Tidak ada throttle di attendance store | **Medium** | Route |
| B8 | CSV injection protection parsial | **Medium** | Report |
| B9 | Double inactive check di middleware + controller | **Low** | Auth |
| MC-3 | Recalculation hapus verifikasi manager tanpa guard | **Critical** | MeritCalculator |
| MC-1 | `$kpi->indicator` null crash jika FK bypassed | **High** | MeritCalculator |
| SB-1 | `SQLite3::backup()` require PHP ≥ 8.3.16 | **High** | SqliteBackup |
| AR-2 | Status priority `NeedsReview` nutup data lokasi | **Medium** | AttendanceRecorder |
| MC-2 | Discipline=100 jika 0 duty trips (tidak adil) | **Medium** | MeritCalculator |
| SB-2 | Tidak ada directory check untuk target backup | **Medium** | SqliteBackup |
| AR-1 | `$receivedAt` tidak dicek terhadap `ends_at` | **Low** | AttendanceRecorder |
| CG-1 | `target_position_id` null crash di CareerGoal | **Low** | CareerGapService |

Detail: `docs/testing_list.md`.

---

## 9. Testing Strategy

**Framework:** PHPUnit 11, SQLite in-memory, `RefreshDatabase` trait.

**Coverage saat ini:** 58 tests, 486 assertions ✅

| Area | File | Tests |
|------|------|-------|
| Auth & Panel Access | `FilamentAccessTest.php` | 15 |
| Duty Trip & Attendance | `DutyAttendanceTest.php` | 13 |
| Merit System | `MeritSystemTest.php` | 11 |
| Career Development | `CareerDevelopmentTest.php` | 9 |
| HR Report & Operations | `OperationsReportTest.php` | 3 |
| Database Seeder | `DatabaseSeederTest.php` | 1 |
| Unit — SqliteBackup | `SqliteBackupTest.php` | 1 |
| Unit — Example | `ExampleTest.php` | 1 |

**Formatting:** Laravel Pint (`vendor/bin/pint --test`)

---

## 10. Infrastruktur

### 10.1. Lokal Development
```bash
composer setup        # install + env + docker mysql + migrate + seed + npm
composer run dev      # artisan serve + queue + pail + vite
composer test         # config:clear + artisan test
```

### 10.2. Database
- **Production:** MySQL 8.4 via Docker (port 3307)
- **Testing:** SQLite in-memory (otomatis dari phpunit.xml)
- **Backup:** `mysqldump` dari host (script di docs/operations.md)

### 10.3. Environment Variables Kunci
```
ATTENDANCE_CLOCK_TOLERANCE_MINUTES=15
PHOTO_RETENTION_DAYS=365
BACKUP_KEEP=14
```

### 10.4. Deployment Checklist (docs/operations.md)
- `APP_ENV=production`, `APP_DEBUG=false`, HTTPS aktif
- Google Maps API key dibatasi domain + API + kuota
- Queue worker + scheduler aktif
- File absensi di disk privat (bukan publik)
- Uji GPS, kamera, offline queue di smartphone nyata

---

## 11. Struktur Direktori

```
app/
├── Enums/           — 6 file (UserRole, DutyTripStatus, AttendanceStatus, dll)
├── Filament/
│   ├── Forms/Components/  — 1 custom (MapPicker)
│   ├── Resources/        — ~86 file, 16 resource groups
│   ├── Widgets/          — 3 (EmployeeStats, ManagerStats, HrStats)
├── Http/
│   ├── Controllers/      — 4 (Attendance, AuthenticatedSession, HrReport, base)
│   ├── Middleware/       — 1 (EnsureUserIsActive)
├── Models/               — 19
├── Providers/
│   ├── Filament/         — 4 panel providers + 1 RolePanelProvider abstract
├── Services/             — 4 (AttendanceRecorder, MeritCalculator, CareerGap, SqliteBackup)
├── Support/              — 1 (GeoDistance/Haversine)
config/hr.php             — Attendance clock tolerance, photo retention, backup keep
database/
├── migrations/           — 7
├── seeders/              — 1 (DatabaseSeeder)
├── factories/            — 1 (UserFactory)
docs/                     — 8 file (brd, implementasi, panel guides, operations, testing_list, revisi, konteks)
resources/views/          — 5 (auth/login, attendance/capture, reports/hr, map-picker, duty-trip-map)
routes/web.php            — 8 routes
tests/                    — 10 file (1 base, 7 feature, 2 unit)
```

---

## 12. Dokumen Terkait

| Dokumen | Isi |
|---------|-----|
| `docs/brd.md` | Business Requirements Document lengkap |
| `docs/implementation-plan.md` | Fase implementasi, teknis, milestone |
| `docs/panel-pegawai.md` | Panduan fitur Panel Pegawai |
| `docs/panel-atasan.md` | Panduan fitur Panel Atasan |
| `docs/panel-hr.md` | Panduan fitur Panel HR |
| `docs/operations.md` | Backup, restore, cron, deployment checklist |
| `docs/testing_list.md` | 110+ skenario testing + 18 bug ditemukan |
| `docs/revisi.md` | Revisi: rekomendasi training oleh atasan + rumus merit + audit trail |

---

## 13. Tujuan Pengembangan ke Depan

### 13.1. Jangka Pendek
- [x] Fix B1: sinkronkan status attendance ke Approved
- [ ] Fix B2: mentoring locking
- [x] Implementasi revisi: rekomendasi training oleh atasan per `docs/revisi.md`
- [ ] Fix B3: validasi max score 5 di PerformanceReview
- [x] Fix B4: trip jangan Completed jika absensi flagged

### 13.2. Jangka Menengah
- [ ] Tampilan breakdown merit di modal rekomendasi training (`meritBreakdownForManager()`)
- [ ] Riwayat KPI dari `activity_logs` untuk audit trail
- [ ] Fix B7: throttle middleware di attendance route
- [ ] Fix B8: escape CSV menggunakan library (League CSV)

### 13.3. Jangka Panjang
- [ ] Notifikasi real-time (Filament notifications / broadcast)
- [ ] Export laporan PDF (bukan hanya CSV)
- [ ] Dashboard analytics grafik per pegawai/periode
- [ ] Role permission package jika kebutuhan akses berkembang
- [ ] Integrasi Google Maps Map Picker (masih perlu FE implementation)
- [ ] Service Worker untuk offline PWA penuh
