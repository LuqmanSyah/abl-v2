# Update Proyek — ABL-v2

## Ringkasan

128 files changed, +9519 / -1096 lines. Branch: `merit-bulanan-status`.

---

## 1. Absensi Harian (Daily Attendance)

**Sebelum:** Satu trip = satu absensi. Trip otomatis `Completed` setelah absen. Tidak bisa absen ulang di hari berbeda.

**Sesudah:** Satu trip multi-hari → absen tiap hari, record terpisah. Trip tetap `Approved` selama periode dinas.

### Perubahan:
- `DutyTrip.attendance()` → `attendances()` (HasMany, bukan HasOne)
- `AttendanceRecorder` → record tiap `captured_at` date, deteksi duplikat per hari. Trip tidak auto-Completed.
- `AttendanceController::show()` → `load('attendances')`
- `capture.blade.php` → cek absensi per `today()` bukan per trip
- Foto tetap di-queue IndexedDB per `client_uuid`

### Test:
- `DutyAttendanceTest` → 13 test (0.97s-0.22s, all green)
- Skenario: radius, late, mock location, clock mismatch, backdate, HR verify, idempotent, offline queue

---

## 2. Formula Disiplin — Per Calendar Day

**Sebelum:** `discipline_score = valid_attendance_count / total_duty_trip_count × 100`. Trip 2 jam = 1 trip = 1 poin.

**Sesudah:** `discipline_score = min(valid_days / total_calendar_days × 100, 100)`. Trip dihitung hari kalender:
```php
$days = $trip->starts_at->startOfDay()->diffInDays($trip->ends_at->startOfDay()) + 1;
```

**Fix bug:** `diffInDays()` return float (0.083 untuk 2 jam). Wajib `startOfDay()` untuk integer.

### Perubahan:
- `MeritCalculator.php:45-52` — iterasi trip, sum calendar days, count valid attendances
- `MeritCalculator.php:52` — `$totalDays ? min($validDays / $totalDays * 100, 100) : 100`

### Formula lengkap:
| Komponen | Formula | Range |
|----------|---------|-------|
| KPI | `SUM(achievement/target × weight)`, cap 120% | 0-120 |
| Disiplin | `valid_days / total_calendar_days × 100`, cap 100 | 0-100 |
| Manager | `AVG(score) / 5 × 100` | 0-100 |
| 360 | `AVG(score) / 5 × 100` | 0-100 |
| Total | `(kpi×w_kpi + disiplin×w_disiplin + manager×w_mgr + 360×w_360) / 100` | 0-108 |

Bobot default: 40/20/20/20, wajib total 100%.

---

## 3. Merit Bulanan + Verifikasi 2-Tahap

### Fitur:
- `MeritResult.calculated_at` — timestamp hitung/update terakhir
- Rekalkulasi hanya sebelum verifikasi Atasan
- Verifikasi 2-tahap: Atasan verify → HR verify + publish → employee lihat
- Setelah publish: immutable, throw `DomainException`
- Merit weight validation di `ReviewPeriod::booted()`

### Komponen baru:
- `MeritResultInfolist` — breakdown score per komponen + KPI history
- `MeritResult` model — `breakdownForManager()`, `verifyByManager()`, `verifyByHr()`, `visibleTo()`
- `reviewScore()` private method + `KpiData` DTO

### Test:
- `MeritSystemTest` → 11 test (all green)
- Skenario: formula exact values, recalculate before verify, immutable after publish, weight validation, 360 review breakup

---

## 4. Manajemen Perintah Dinas + Peta

### Fitur:
- Map picker komponen (`latitude`/`longitude` dari klik peta)
- `DutyTripForm` — geofence dengan radius
- `DutyTripsTable` — filter status, employee scope
- Trip hanya bisa diubah/dibatalkan sebelum `starts_at` dan tidak ada absensi

### Test:
- 10 skenario duty trip CRUD + scope

---

## 5. Sistem Rekomendasi Pelatihan oleh Atasan

### Fitur:
- Method `TrainingRequest::recommendByManager()` — snapshot komponen merit
- Modal breakdown merit untuk Atasan (`recommend-training-breakdown.blade.php`)
- `MeritResult.breakdownForManager()` — KPI history + review + disiplin detail
- Audit trail di `activity_logs`

### Test:
- Integration test: modal muncul → pilih training + alasan → submit → snapshotted

---

## 6. Panel Peran (Role-based Panels)

### Fitur:
- 3 panel terpisah: `/pegawai`, `/atasan`, `/hr`
- `HandleForbiddenPanelPage` middleware — arahkan ke panel sesuai role
- `EnsureUserIsActive` middleware — logout jika nonaktif
- `AuthenticatedSessionController` — login flow + redirect ke panel
- Widgets: `EmployeeStats`, `ManagerStats`, `HrStats`

### Filament Resources:
- Attendance, DutyTrip, KPI, Merit, PerformanceReview, Mentoring, Training, Competency, Career, dll
- Akses per role: Employee hanya lihat milik sendiri, Manager lihat bawahan, HR lihat semua

---

## 7. Keamanan & Middleware

- `EnsureUserIsActive` — cegah user nonaktif akses panel
- `HandleForbiddenPanelPage` — redirect ke panel benar
- `authenticated` guard di route `attendance.*`
- `canAttend()` — cek role Employee, active, trip milik sendiri, status Approved/Completed
- Database transaction + rollback jika Attendance fail
- Photo access: Employee hanya fotonya sendiri, Manager bawahan, HR semua

---

## 8. Login Page Redesign

- Responsive + dark gradient + ilustrasi
- Password toggle (eye icon)
- Localized validation messages (`lang/id/validation.php`)
- Inactive user detection + logout
- CSRF + remember me

---

## 9. Laporan SDM (HR Report)

- `HrReportController` — filter by period/unit/jabatan
- CSV export dengan formula protection (`=`, `+`, `-`, `@` prefix)
- Kolom: absensi total & valid, merit score, training selesai, mentoring selesai
- `OperationsReportTest` → filter + export test

---

## 10. Operasional & Infra

- `compose.yaml` — Docker MySQL (port 3307)
- `.env.example` — updated dengan DB config
- Photo purge artisan command
- MySQL backup/restore scripts
- `app/config/hr.php` — konfigurasi HR

### Seeder:
- `DatabaseSeeder` diperluas: 3 unit, 3 jabatan, kompetensi, KPI, periode penilaian, 20+ user dengan relasi, duty trips, absensi, mentoring, training, performance reviews, merit results

---

## 11. Dokumentasi

| File | Isi |
|------|-----|
| `docs/konteks.md` | Proyek overview, komponen per modul, data architecture, routing |
| `docs/revisi.md` | Rekomendasi training + rumus merit + audit trail + UI rev |
| `docs/panel-pegawai.md` | Panduan fitur Panel Pegawai |
| `docs/panel-atasan.md` | Panduan fitur Panel Atasan |
| `docs/panel-hr.md` | Panduan fitur Panel HR |
| `docs/brd.md` | Business Requirements Document v1.3 |
| `docs/implementation-plan.md` | Fase implementasi |
| `docs/operations.md` | Operasional & deployment |
| `docs/sprint-merit-bulanan.md` | Scope sprint merit |
| `docs/testing_list.md` | 190+ skenario uji + 18 bug tracker |
| `docs/update_saat_ini.md` | File ini |

---

## Bug Tracker Status

| ID | Deskripsi | Status |
|----|-----------|--------|
| B1 | `canAttend()` vs `record()` status conflict | **Open** — Completed trip masih tampil form |
| B4 | Trip auto-Completed untuk semua status | **Fixed** — tidak auto-Completed lagi |
| MC-2 | Discipline = 100 jika 0 trips | **Open** — masih `$totalDays ? ... : 100` |
| Lainnya | 16 bug lain di testing_list.md | Open |

---

## Test Suite

**24 test passing**, 0 failing (110 assertions, 2.55s):
- DutyAttendanceTest: 13 ✓
- MeritSystemTest: 11 ✓
- FilamentAccessTest, CareerDevelopmentTest, OperationsReportTest, DatabaseSeederTest: all ✓
