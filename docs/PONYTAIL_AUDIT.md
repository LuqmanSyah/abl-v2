# 🔍 Ponytail Audit — Blueprint HRIS v3 Implementation

> **Tanggal:** 2026-07-26
> **Cakupan:** Full tree scan terhadap implementasi [EXECUTION_PLAN.md](./EXECUTION_PLAN.md) + [SOMETHING_NEW.md](./SOMETHING_NEW.md)
> **Metode:** Ponytail over-engineering audit — bukan review kebenaran, murni audit efisiensi kode
> **Total app/ lines:** ~5.583 baris PHP

---

## Findings (ranked by biggest cut first)

| # | Tag | What to Cut | Replacement | Path |
|---|-----|-------------|-------------|------|
| 1 | **delete** | `HrReportController` — 229 baris, referensi model lama (`ReviewPeriod`, `Unit`, `MentoringStatus`, `TrainingRequestStatus`, `AttendanceRecorder`, `DutyTrip`). Tidak akan compile. Tidak ada di blueprint/plan. | Hapus. Jika laporan HR diperlukan, bangun ulang di atas model blueprint nanti. | `app/Http/Controllers/HrReportController.php` |
| 2 | **delete** | `AttendanceController` — 116 baris, referensi model lama (`DutyTrip`, `DutyTripStatus`, `AttendanceRecorder`). Blueprint absensi sekarang melalui Filament `AttendanceResource`. | Hapus. Fungsionalitas sudah di-cover oleh `AttendanceService` + Filament resource. | `app/Http/Controllers/AttendanceController.php` |
| 3 | **delete** | `FaceVerificationController` — 72 baris, memanggil Python script (`face_extract.py`) via subprocess. Fitur face verification tidak ada di blueprint. | Hapus controller + `resources/python/face_extract.py`. Jika dibutuhkan nanti, implementasi ulang. | `app/Http/Controllers/FaceVerificationController.php` |
| 4 | **delete** | `ReportMail` + view — 57 baris. Referensi DomPDF + view lama. Tidak ada di blueprint. Sistem notifikasi pakai WebPush + Database. | Hapus `app/Mail/ReportMail.php`, `resources/views/emails/report.blade.php`, `resources/views/reports/hr-pdf.blade.php`, `resources/views/reports/hr.blade.php`. | `app/Mail/` + `resources/views/reports/` + `resources/views/emails/` |
| 5 | **delete** | `AuthenticatedSessionController` — 46 baris. Redirect pakai `$user->role->value` (misal `/employee`). Filament sudah punya login built-in via panel providers. | Hapus. Pakai Filament native login + `canAccessPanel()` di User model. | `app/Http/Controllers/AuthenticatedSessionController.php` |
| 6 | **delete** | Blade views dari codebase lama — `attendance/capture.blade.php`, `auth/login.blade.php`, `filament/infolists/duty-trip-map.blade.php`, `filament/resources/merit-results/recommend-training-breakdown.blade.php`. Referensi entitas yang sudah tidak ada. | Hapus. Filament resource pages menggantikan view custom. | `resources/views/attendance/`, `resources/views/auth/`, beberapa di `resources/views/filament/` |
| 7 | **shrink** | Model `booted()` hooks — LeaveRequest (121L), AttendanceRequest (123L), PerformanceReview (84L), Promotion (80L), Holiday (62L). Total ~470 baris business logic, overlap validation, notification dispatch, dan cross-table writes langsung di model lifecycle. | Extract side-effects ke Observer per model, atau Action classes. Model hanya schema + relationships. Estimasi net -100 baris dari deduplication overlap logic antara `LeaveRequest` dan `AttendanceRequest`. | `app/Models/LeaveRequest.php`, `AttendanceRequest.php`, `PerformanceReview.php`, `Promotion.php`, `Holiday.php` |
| 8 | **native** | `GoogleMapsService::distance()` — 64 baris wrapper Google Distance Matrix API + 3x retry untuk geofencing radius check. Haversine (`GeoDistance`) sudah ada di codebase dan cukup untuk straight-line radius validation. Distance Matrix API menghitung jarak tempuh jalan, bukan jarak lurus. | Pakai `GeoDistance::meters()` langsung untuk geofencing. Pertahankan hanya `reverseGeocode()` sebagai satu-satunya method yang butuh Google API. ~30 baris terhapus dari service, ~20 baris retry logic di `AttendanceService`. | `app/Services/GoogleMapsService.php` + `app/Services/AttendanceService.php` |
| 9 | **yagni** | `RoleAwareResource` — 65 baris abstract base class. Hardcode semua child class dalam central `match(static::class)` block. Setiap resource baru harus diedit di parent class. | Inline `canAccess()` di setiap resource yang butuhnya (1-3 baris per resource), atau pakai Filament native policy. Hapus abstract class. | `app/Filament/Resources/RoleAwareResource.php` |
| 10 | **yagni** | `ReadinessScoreService` — 32 baris, satu public method, satu consumer (`ScanCandidatePool`). | Pindahkan `calculate()` sebagai method di `User` model: `$user->readinessScoreFor($position)`. Hapus file service. | `app/Services/ReadinessScoreService.php` |
| 11 | **yagni** | `MeritScoreService` — 44 baris, satu public method, satu consumer flow. | Pindahkan sebagai method di `PerformanceReview` model: `$review->recalculateMerit()`. Hapus file service. | `app/Services/MeritScoreService.php` |
| 12 | **shrink** | `AttendanceDataChanged` event + `RecalculateMeritOnChange` listener — listener meng-inject Artisan Command class (`AggregateDailyAttendance`) ke constructor. Coupling event system ke CLI command. | Listener langsung panggil service/query. Atau command dispatch event, bukan sebaliknya. | `app/Listeners/RecalculateMeritOnChange.php` |
| 13 | **native** | `BusinessRuleException` — 7 baris. Empty class extending `DomainException`. Zero tambahan behavior. | Pakai `DomainException` langsung, atau `\InvalidArgumentException`. Atau pertahankan sebagai semantic alias (biaya 0, benefit = greppability). | `app/Exceptions/BusinessRuleException.php` |
| 14 | **delete** | `inspire` command di `routes/console.php` — Laravel default boilerplate, tidak dipakai. | Hapus 3 baris. | `routes/console.php` L7-9 |
| 15 | **delete** | `db:backup` scheduled command — terdaftar di `routes/console.php` tapi `BackupDatabase` command class tidak ada. Runtime error. | Hapus schedule entry, atau buat command-nya. | `routes/console.php` L23-25 |
| 16 | **delete** | `app/Http/Middleware/` — empty directory. | Hapus directory. | `app/Http/Middleware/` |
| 17 | **delete** | `app/Http/Controllers/Controller.php` — 77 bytes, empty base class. Dengan semua custom controllers dihapus (finding 1-5), tidak ada consumer. | Hapus. | `app/Http/Controllers/Controller.php` |
| 18 | **yagni** | `WorkflowNotification` base class — 38 baris. 8 child classes. | Pertahankan. 8 consumers justify abstraction — ini kasus yang sah. Tapi bisa jadi Trait (5 baris lebih pendek). | `app/Notifications/WorkflowNotification.php` |

---

## Missing dari Blueprint Plan

| Item | Status | Notes |
|------|--------|-------|
| `DepartmentResource` | ❌ Tidak ada | Planned di execution plan, belum dibuat |
| `PositionResource` | ❌ Tidak ada | Planned di execution plan, belum dibuat |
| `SkillResource` | ❌ Tidak ada | Planned di execution plan, belum dibuat |
| `UserResource` | ❌ Tidak ada | Planned di execution plan (IT Admin CRUD), belum dibuat |
| `BackupDatabase` command | ❌ Tidak ada | Scheduled tapi command class tidak ada. Akan error saat scheduler run. |

---

## Ghost References (Kode Lama yang Masih Tertinggal)

File-file berikut mereferensi model/enum dari codebase lama yang **tidak ada** di blueprint v3:

| Reference | Found In | Problem |
|-----------|----------|---------|
| `DutyTrip` model | `AttendanceController`, `ActiveDutyTrips` widget | Model tidak ada di blueprint |
| `DutyTripStatus` enum | `AttendanceController` | Enum tidak ada |
| `ReviewPeriod` model | `HrReportController` | Model tidak ada di blueprint |
| `Unit` model | `HrReportController` | Diganti `Department` di blueprint |
| `MentoringStatus` enum | `HrReportController` | Enum tidak ada |
| `TrainingRequestStatus` enum | `HrReportController` | Enum tidak ada |
| `AttendanceRecorder` service | `AttendanceController` | Service tidak ada, diganti `AttendanceService` |
| `UserRole::Hr` | `HrReportController` | Blueprint pakai `UserRole::HrAdmin` |
| `AttendanceStatus::Valid` | `HrReportController` | Blueprint pakai `Normal`/`Late`/`PendingVerification`/`Rejected` |

---

## Net Summary

| Metric | Count |
|--------|-------|
| **Files removable** | ~15 files (controllers, mail, old views, empty dirs) |
| **Lines removable (delete)** | ~620 baris (dead code dari codebase lama) |
| **Lines reducible (shrink/yagni)** | ~200 baris (service → model method, observer extraction) |
| **Dependencies removable** | `barryvdh/laravel-dompdf` (jika ReportMail dihapus), `league/csv`, `openspout/openspout` (hanya dipakai HrReportController) |
| **Ghost references** | 9 references ke model/enum yang tidak exist |
| **Missing from plan** | 5 items (4 Filament resources + 1 command) |

---

> **Verdict:** Codebase ada sisa substansial dari codebase lama (~620 baris dead code) yang akan menyebabkan runtime error karena mereferensi model/enum yang sudah dihapus. Prioritas 1: hapus semua file di `app/Http/Controllers/` (kecuali `WebPushController.php` jika masih dipakai), `app/Mail/`, dan view lama. Prioritas 2: inline service kecil ke model. Core architecture (19 models, enums, migrations, seeders, commands, widgets) solid dan sesuai blueprint.
