# Update Proyek — ABL-v2

## Ringkasan

146 files changed, +9780 / -1100 lines. Branch: `merit-bulanan-status`.

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

## 9. Laporan SDM (HR Report) + Report Builder

**Enhanced:** Report builder dengan column selection, group by, XLSX export.

### Perubahan:

| File | Perubahan |
|------|-----------|
| `app/Http/Controllers/HrReportController.php` | `AVAILABLE_COLUMNS` constant; `resolveColumns()`; `group_by` filter; grouped rows; XLSX export via openspout |
| `resources/views/reports/hr.blade.php` | Column checkboxes (details toggle), group by select, dynamic table headers, group rows, XLSX button, summary count |
| `resources/views/reports/hr-pdf.blade.php` | Dynamic columns + group rows |
| `routes/web.php` | `hr.reports.xlsx` route |
| `composer.json` | `openspout/openspout` for XLSX generation |

### Fitur baru:
- **Pilih kolom:** checklist kolom yang ditampilkan (disimpan di URL)
- **Kelompokkan:** group by unit atau jabatan (tampilkan header grup)
- **Export XLSX:** format Excel (`.xlsx`) via openspout
- **Dynamic table:** header + data menyesuaikan kolom terpilih

---

## 10. Face Verification

**Fitur baru:** Verifikasi wajah otomatis saat absen menggunakan face-api.js (client-side).

### Alur:
1. Saat ambil foto, JS ekstrak 128-dim face descriptor via `@vladmandic/face-api`
2. Descriptor dikirim sebagai JSON `face_descriptor` di form data
3. Server simpan `face_descriptor` ke kolom baru `attendances.face_descriptor`
4. Server bandingkan dengan descriptor absensi sebelumnya (employee + trip sama)
5. Euclidean distance > 0.6 → status diubah ke `NeedsReview`

### Perubahan:

| File | Perubahan |
|------|-----------|
| `public/js/face-api.js` | Library @vladmandic/face-api (1.3 MB) |
| `public/js/face-verification.js` | Modul wrapper: init, extractDescriptor, verify |
| `public/models/*.bin` + `*manifest.json` | Model files (tiny_face_detector, face_landmark_68, face_recognition) — ~6.6 MB total |
| `app/Http/Controllers/AttendanceController.php` | `show()`: passing `previousDescriptor`; `store()`: validasi `face_descriptor` |
| `resources/views/attendance/capture.blade.php` | Load face-api.js + face-verification.js; face verification di submit handler; status messaging |
| `database/migrations/2026_07_19_000000_add_face_descriptor_to_attendances.php` | Migration: tambah `face_descriptor` TEXT ke `attendances` |
| `app/Models/Attendance.php` | `$fillable` + `face_descriptor` |
| `app/Services/AttendanceRecorder.php` | Server-side face descriptor comparison dengan Euclidean distance |

### Catatan:
- Model files di-cache oleh Service Worker setelah first load
- Jika face-api tidak tersedia (gagal load / offline first-time), absensi tetap diproses tanpa data wajah (fallback)
- Threshold 0.6 adalah default face-api.js recommendation

---

## 11. Operasional & Infra

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
| `docs/sprint_project.md` | Scope sprint merit & project |
| `docs/testing_list.md` | 190+ skenario uji + 18 bug tracker |
| `docs/next_update.md` | Roadmap pengembangan — notifikasi, scheduler, lanjutan |

---

## 12. Revisi Bug & Keamanan (Batch 1 — 6 fix, 1 cancel, 1 open)

| # | Service | Severity | Issue | Status |
|---|---------|----------|-------|--------|
| 1 | Merit | Critical | `with('attendance')` crash | ✅ Fixed |
| 2 | Attendance | High | `canAttend()` vs `record()` conflict | ✅ Fixed |
| 3 | Career | High | Mentoring tanpa DB locking | ✅ Fixed |
| 4 | Merit | Medium | MC-2: 0 trips = 100 score → `: 0` | ✅ Fixed |
| 5 | Merit | Low | B5: `$average` falsy bug | ✅ Fixed |
| 6 | Career | Low | CG-1: `target_position_id` null crash | ✅ Fixed |
| 7 | Attendance | Medium | AR-2: status priority nutup data | ❌ Open (butuh DB migration) |
| 8 | Attendance | Medium | AR-1: ends_at block | ⛔ Cancelled (konflik Late test) |

---

## 13. Multi-level Approval Workflow + Delegasi + Eskalasi

**Fitur baru:** Generic workflow engine, delegasi atasan, eskalasi otomatis.

### Perubahan:

| File | Perubahan |
|------|-----------|
| `app/Models/Concerns/HasWorkflow.php` | Trait: `workflowTransition()` (lockForUpdate + re-read), `guardRole()`, `delegateCanAct()` |
| `app/Models/TrainingRequest.php` | Refactor `transition()` → `workflowTransition()`; delegation via `actorIsManager()` |
| `app/Models/Mentoring.php` | Refactor `transition()` → `workflowTransition()`; delegation via `actorIsManager()` |
| `app/Models/User.php` | `delegate_id` fillable; `delegate()` + `delegatedFrom()` relations |
| `database/migrations/2026_07_19_010000_add_delegate_id_to_users.php` | Tambah `delegate_id` nullable FK ke `users` |
| `app/Console/Commands/EscalateApprovals.php` | `approval:escalate` — pending TrainingRequest/Mentoring >3 hari → notif HR |
| `routes/console.php` | `approval:escalate` daily at 06:00 |

### Detail:
- **Workflow engine:** Trait `HasWorkflow` dengan `workflowTransition(callable)` — reload model in transaction with `lockForUpdate`, terapkan transisi, setRawAttributes. Siap pakai untuk model mana pun.
- **Delegasi:** Manager set `delegate_id` (Manager lain). Delegate bisa approve/reject atas nama manager. Tercatat di activity_log sebagai `delegated_approval`.
- **Eskalasi:** Scheduler tiap jam 06:00 cek TrainingRequest `pending_manager` + Mentoring `pending` yang sudah >3 hari. Kirim notifikasi ke seluruh HR.

---

## 14. Notifikasi & Scheduler Otomatis (Sprint 1 & 2)

### 13.1. Notifikasi (9 kelas)

| Notifikasi | Trigger | Penerima | Channel |
|-----------|---------|----------|---------|
| `TripAssigned` | DutyTrip created | Employee | DB + Email |
| `AttendanceReminder` | Scheduler harian | Employee | DB + Email |
| `AttendanceNeedsReview` | Attendance status NeedsReview | Manager + HR | DB + Email |
| `MentoringPending` | Mentoring created | Manager | DB |
| `MentoringScheduled` | Mentoring approved | Employee | DB |
| `MeritPublished` | MeritResult verifyByHr | Employee | DB + Email |
| `MeritReadyForVerification` | MeritResult calculated | Manager | DB |
| `KpiDeadlineReminder` | Scheduler harian | Manager | DB |
| `TrainingPending` | TrainingRequest employee create | Manager | DB |

### 13.2. Scheduler (3 command)

| Command | Jadwal | Fungsi |
|---------|--------|--------|
| `merit:calculate` | Tiap tgl 1, 00:05 | Hitung merit semua periode aktif |
| `merit:remind-kpi` | Setiap hari 09:00 | Ingatkan manager yang belum input KPI |
| `attendance:remind` | Setiap hari 08:00 & 12:00 | Ingatkan employee yang belum absen hari ini |

### 13.3. Infra

- `notifications` table via `php artisan notifications:table`
- `User` sudah `Notifiable` (trait existing)
- Notifikasi queueable via `Queueable` trait
- Semua scheduler pakai `->dailyAt()` / `->twiceDaily()` / `->monthlyOn()`

---

## Bug Tracker Status

| ID | Deskripsi | Status |
|----|-----------|--------|
| B1 | `canAttend()` vs `record()` status conflict | **Fixed** — disinkronkan ke `Approved` saja |
| B2 | Mentoring state tanpa DB locking | **Fixed** — implementasi `transition()` lockForUpdate |
| B4 | Trip auto-Completed untuk semua status | **Fixed** — tidak auto-Completed lagi |
| B5 | `reviewScore()` falsy `$average ?` bug | **Fixed** — `$average !== null` |
| MC-2 | Discipline = 100 jika 0 trips | **Fixed** — fallback `: 100` → `: 0` |
| CG-1 | `target_position_id` null crash | **Fixed** — early return guard |
| AR-2 | Status priority nutup data lokasi | **Open** — perlu DB migration |
| Lainnya | 11 bug lain di testing_list.md | Open |

---

## Test Suite

**58 test passing**, 0 failing (485 assertions, 5.90s):
- DutyAttendanceTest: 13 ✓
- MeritSystemTest: 11 ✓
- CareerDevelopmentTest: 9 ✓
- FilamentAccessTest: 15 ✓
- FlowTest: 1 ✓
- OperationsReportTest: 3 ✓
- DatabaseSeederTest: 1 ✓
- ExampleTest: 3 ✓
- Unit/SqliteBackupTest: 1 ✓

**Coverage gap:** Notifikasi terpicu via observer/event — perlu test integration notifikasi menyala di skenario yang tepat.
