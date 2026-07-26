# Execution Plan: Full Rewrite — Blueprint HRIS v3

> **Source of Truth:** [SOMETHING_NEW.md](./SOMETHING_NEW.md) (Immutable)  
> **Branch:** `rewrite/blueprint-v3`  
> **Stack:** Laravel 12 + Filament v5 + Docker (PostgreSQL/MySQL + Redis) + Google Maps API  
> **Estimated Effort:** 3–4 minggu full-time solo

---

## Prinsip Rewrite

1. **Blueprint = hukum.** Semua 19 tabel, relasi, dan business rules dari blueprint diimplementasikan persis.
2. **Port quality, bukan code.** Patterns bagus dari codebase lama (Enums, `BusinessRuleException`, Services, `GeoDistance`, Notifications) di-port dan diadaptasi — bukan copy-paste.
3. **Setiap phase punya verification step.** Tidak lanjut ke phase berikutnya sebelum phase sekarang verified.
4. **Commit atomic per sub-task.** Setiap file group = 1 commit dengan conventional commit message.

---

## Phase 0: Persiapan (Hari 1)

### Objective
Bersihkan slate, setup branch, konfigurasi dasar.

### Tasks

#### 0.1 — Branch & Cleanup
- [x] `git checkout -b rewrite/blueprint-v3`
- [x] Hapus semua migration files kecuali `0001_01_01_000000_create_users_table.php` (Laravel default)
- [x] Hapus semua migration tambahan (`2026_07_*`)
- [x] Hapus semua Models di `app/Models/` kecuali `User.php`
- [x] Hapus semua Filament Resources di `app/Filament/Resources/`
- [x] Hapus semua Filament Widgets di `app/Filament/Widgets/`
- [x] Hapus Panel Providers: `HrPanelProvider.php`, `ManagerPanelProvider.php`, `EmployeePanelProvider.php`, `RolePanelProvider.php`
- [x] Hapus semua Seeders kecuali `DatabaseSeeder.php`
- [x] Hapus `app/Services/` (akan ditulis ulang)
- [x] Hapus `app/Console/Commands/` (akan ditulis ulang)
- [x] Hapus `app/Enums/` (akan ditulis ulang sesuai blueprint)
- [x] Hapus `app/Notifications/` (akan ditulis ulang)
- [x] Bersihkan `routes/console.php` (kosongkan schedule)

#### 0.2 — Konfigurasi
- [x] `config/app.php`: timezone = `Asia/Jakarta`
- [x] Verify Docker container timezone = `Asia/Jakarta` (di `compose.yaml`)
- [x] `.env`: `GOOGLE_MAPS_API_KEY` placeholder
- [x] `config/services.php`: tambah `google_maps` config block

#### 0.3 — Port Utility & Base Classes
- [x] Port `app/Support/GeoDistance.php` (Haversine formula) — reuse as-is
- [x] Port `app/Exceptions/BusinessRuleException.php` — reuse as-is
- [x] Buat `app/Enums/` directory (isi di Phase 1)

#### Verification
```bash
php artisan migrate:fresh   # hanya users, sessions, cache, jobs tables
php artisan serve            # app boots tanpa error
```

#### Commit
```
chore: clean slate for blueprint rewrite
```

---

## Phase 1: Foundation — Schema, Models, Enums, Seeders (Minggu 1)

### Objective
19 tabel dari blueprint terbuat, semua Models dengan Eloquent relationships, Enums untuk semua status fields, Seeders dasar.

---

### 1.1 — Enums

File-file baru di `app/Enums/`:

| File | Values | Dipakai di |
|------|--------|------------|
| `UserRole.php` | `Employee`, `Manager`, `HrAdmin`, `Director`, `ItAdmin` | `users.role` — blueprint Section 3 |
| `LeaveType.php` | `Sick`, `PaidLeave`, `Permit` | `leave_requests.type` |
| `LeaveStatus.php` | `Pending`, `Approved`, `Rejected` | `leave_requests.status` |
| `FlowType.php` | `TopDown`, `BottomUp` | `attendance_requests.flow_type` |
| `AttendanceRequestStatus.php` | `Pending`, `Approved`, `Rejected`, `Cancelled` | `attendance_requests.status` |
| `AttendanceType.php` | `CheckIn`, `CheckOut` | `attendances.type` |
| `AttendanceStatus.php` | `Normal`, `Late`, `PendingVerification`, `Rejected` | `attendances.status` |
| `DailySummaryStatus.php` | `Present`, `Late`, `Alfa`, `Leave`, `Holiday`, `MissingCheckout` | `daily_attendance_summaries.status` |
| `ReviewStatus.php` | `Draft`, `Submitted`, `Approved`, `Locked` | `performance_reviews.status` |
| `PromotionStatus.php` | `Proposed`, `ApprovedByHr`, `ApprovedByDirector`, `Rejected`, `Expired` | `promotions.status` |
| `IdpStatus.php` | `Active`, `Completed`, `Cancelled` | `individual_development_plans.status` |

#### Commit
```
feat: add all blueprint enums
```

---

### 1.2 — Migrations

Semua migration baru. Penamaan: `2026_07_26_XXXXXX_create_*.php`

#### Migration 1: `create_organization_tables.php`
Tabel: `departments`, `positions`, `work_schedules`, `branch_offices`, `skills`, `position_skills`

```
departments:     id, name, code
positions:       id, department_id(FK), title, level
work_schedules:  id, name, check_in_time, check_out_time, late_tolerance_minutes, alfa_cutoff_minutes
branch_offices:  id, name, code, latitude, longitude, allowed_radius_meters
skills:          id, name, category
position_skills: id, position_id(FK), skill_id(FK), min_required_level
```

#### Migration 2: `add_blueprint_fields_to_users.php`
Alter `users` table — tambah fields blueprint:

```
Tambah:  nip(unique), position_id(FK), work_schedule_id(FK), branch_office_id(FK), 
         manager_id(FK self), join_date, status(boolean), role(string/enum)
Hapus:   unit_id, duty_location_id, employee_number, phone (jika ada)
```

> **Note:** `users` table sudah ada dari Laravel default migration. Pakai `Schema::table()` untuk alter.

#### Migration 3: `create_user_skills_table.php`
```
user_skills: id, user_id(FK), skill_id(FK), current_level
```

#### Migration 4: `create_attendance_tables.php`
```
holidays:          id, name, date(unique)
leave_requests:    id, user_id(FK), type, start_date, end_date, reason, status, approved_by(FK nullable), approved_at(nullable)
attendance_requests: id, user_id(FK), created_by(FK), flow_type, destination_name, destination_address,
                     target_latitude, target_longitude, allowed_radius_meters, 
                     duty_start_datetime, duty_end_datetime, reason, status, approved_by(FK nullable)
attendances:       id, user_id(FK), attendance_request_id(FK nullable), type, latitude, longitude,
                   distance_to_target_meters, is_fallback(bool), address_snapshot, photo_path,
                   is_radius_exception(bool), exception_reason(nullable), status, recorded_at
daily_attendance_summaries: id, user_id(FK), attendance_request_id(FK nullable), date, 
                            check_in_id(FK attendances nullable), check_out_id(FK attendances nullable),
                            status, late_minutes
```

#### Migration 5: `create_merit_tables.php`
```
kpis:               id, name, category, weight
performance_reviews: id, user_id(FK), reviewer_id(FK), period, start_date, end_date,
                     attendance_score, manager_kpi_score, final_merit_score, grade, status
review_kpi_details:  id, performance_review_id(FK), kpi_id(FK), self_score, self_notes,
                     manager_score, manager_notes, weight, subtotal_score
```

#### Migration 6: `create_career_tables.php`
```
career_paths:                 id, current_position_id(FK), next_position_id(FK), min_experience_months, min_merit_grade
individual_development_plans: id, user_id(FK), mentor_id(FK), title, action_plan, 
                              progress_percentage, target_completion_date, status
promotions:                   id, user_id(FK), from_position_id(FK), to_position_id(FK), 
                              proposed_by(FK), readiness_score, status, effective_date
```

#### Verification
```bash
php artisan migrate:fresh
# Semua 19 tabel + Laravel default tables terbuat tanpa error
php artisan migrate:rollback
# Semua rollback clean
```

#### Commit
```
feat: create all 19 blueprint database tables
```

---

### 1.3 — Models + Relationships

19 Models di `app/Models/`. Relasi persis blueprint Section 5.

| Model | File | Key Relationships |
|-------|------|-------------------|
| `Department` | `Department.php` | `hasMany(Position)` |
| `Position` | `Position.php` | `belongsTo(Department)`, `hasMany(PositionSkill)`, `hasMany(User)` |
| `WorkSchedule` | `WorkSchedule.php` | `hasMany(User)` |
| `BranchOffice` | `BranchOffice.php` | `hasMany(User)` |
| `Skill` | `Skill.php` | `hasMany(PositionSkill)`, `hasMany(UserSkill)` |
| `PositionSkill` | `PositionSkill.php` | `belongsTo(Position)`, `belongsTo(Skill)` |
| `UserSkill` | `UserSkill.php` | `belongsTo(User)`, `belongsTo(Skill)` |
| `User` | `User.php` | `belongsTo(Position, WorkSchedule, BranchOffice, User:manager_id)`, `hasMany(UserSkill, LeaveRequest, AttendanceRequest, Attendance, DailyAttendanceSummary, PerformanceReview, IndividualDevelopmentPlan, Promotion)` |
| `Holiday` | `Holiday.php` | — (standalone) |
| `LeaveRequest` | `LeaveRequest.php` | `belongsTo(User)`, `belongsTo(User:approved_by)` |
| `AttendanceRequest` | `AttendanceRequest.php` | `belongsTo(User)`, `belongsTo(User:created_by)`, `belongsTo(User:approved_by)`, `hasMany(Attendance)`, `hasMany(DailyAttendanceSummary)` |
| `Attendance` | `Attendance.php` | `belongsTo(User)`, `belongsTo(AttendanceRequest)` |
| `DailyAttendanceSummary` | `DailyAttendanceSummary.php` | `belongsTo(User, AttendanceRequest, Attendance:check_in_id, Attendance:check_out_id)` |
| `Kpi` | `Kpi.php` | `hasMany(ReviewKpiDetail)` |
| `PerformanceReview` | `PerformanceReview.php` | `belongsTo(User)`, `belongsTo(User:reviewer_id)`, `hasMany(ReviewKpiDetail)` |
| `ReviewKpiDetail` | `ReviewKpiDetail.php` | `belongsTo(PerformanceReview)`, `belongsTo(Kpi)` |
| `CareerPath` | `CareerPath.php` | `belongsTo(Position:current_position_id)`, `belongsTo(Position:next_position_id)` |
| `IndividualDevelopmentPlan` | `IndividualDevelopmentPlan.php` | `belongsTo(User)`, `belongsTo(User:mentor_id)` |
| `Promotion` | `Promotion.php` | `belongsTo(User)`, `belongsTo(User:proposed_by)`, `belongsTo(Position:from_position_id)`, `belongsTo(Position:to_position_id)` |

#### Port dari codebase lama
- `User.php`: port `canAccessPanel()` logic, adapt untuk 2 panel + 5 roles
- `User.php`: port `booted()` validation hooks, adapt field names
- Semua models: pakai `$casts` array dengan Enum types
- Semua models: pakai `$fillable` strictly matching blueprint columns

#### Verification
```bash
php artisan tinker
# Test setiap relasi: User::factory()->create(); $user->position; etc.
```

#### Commit
```
feat: add all 19 blueprint models with eloquent relationships
```

---

### 1.4 — Seeders

| Seeder | Data |
|--------|------|
| `DepartmentSeeder` | 3-5 departments |
| `PositionSeeder` | 5-10 positions linked to departments |
| `WorkScheduleSeeder` | 2-3 schedules (reguler 08:00-17:00, shift 07:00-16:00) |
| `BranchOfficeSeeder` | 2-3 kantor cabang dengan GPS coordinates |
| `SkillSeeder` | 5-10 skills |
| `PositionSkillSeeder` | Link skills ke positions |
| `HolidaySeeder` | 10-15 hari libur nasional 2026 |
| `UserSeeder` | 1 admin, 2 managers, 5 employees, 1 HR, 1 director |
| `KpiSeeder` | 5-8 KPI indicators |
| `CareerPathSeeder` | 3-5 career paths |

#### Verification
```bash
php artisan migrate:fresh --seed
# Semua seed tanpa error, data konsisten
```

#### Commit
```
feat: add blueprint seeders with realistic data
```

---

## Phase 2: Layanan 1 — Attendance System (Minggu 2)

### Objective
GPS attendance (kantor biasa + tugas luar), geofencing, leave management, daily aggregation cron.

---

### 2.1 — Leave Management

#### Files
- `app/Filament/Resources/LeaveRequestResource.php` + Pages (CRUD)
- Business logic di `LeaveRequest` model `booted()`:
  - Approval sets `approved_by` + `approved_at`
  - On approval: auto-create/overwrite `daily_attendance_summaries` dengan `status = 'leave'` untuk rentang `start_date` s/d `end_date` (blueprint Section 2A.6)
  - Cross-module overlap validation terhadap `attendance_requests` (blueprint Section 2A.2)

#### Verification
```bash
# Test: approve leave request, verify daily_attendance_summaries terisi
# Test: create leave overlapping dengan approved attendance_request, verify ditolak
```

#### Commit
```
feat: leave request CRUD with auto daily summary creation
```

---

### 2.2 — Attendance Request (Tugas Luar)

#### Files
- [x] `app/Filament/Resources/AttendanceRequestResource.php` + Pages
- [x] Business logic di `AttendanceRequest` model:
  - [x] Top-down: auto-approve (`status = approved`, `approved_by = created_by`)
  - [x] Bottom-up: `status = pending`, butuh approval
  - [x] Overlap validation: cek terhadap `attendance_requests` lain DAN `leave_requests` approved (formula interval overlap dari blueprint)
  - [x] Multi-hari: 1 `attendance_request` bisa span beberapa hari

#### Verification
```bash
# Test: top-down auto-approve
# Test: bottom-up pending flow
# Test: overlap rejection
```

#### Commit
```
feat: attendance request with top-down/bottom-up flow and overlap validation
```

---

### 2.3 — GPS Attendance Service

#### Files
- `app/Services/AttendanceService.php` — core logic:
  - Determine target location:
    - `attendance_request_id = NULL`: pakai `branch_offices` coordinates (user's `branch_office_id`)
    - `attendance_request_id` terisi: pakai `attendance_requests` target coordinates
  - Early check-in window: aktif 90 menit sebelum `check_in_time` / `duty_start_datetime`
  - Alfa cutoff: > `alfa_cutoff_minutes` setelah jam masuk = status Alfa
  - Geofencing dual-check:
    1. Google Distance Matrix API call
    2. 3x retry dengan 2s delay
    3. Fallback Haversine (reuse `GeoDistance`)
    4. `is_fallback = true` jika pakai Haversine
  - Check-out exception: luar radius = wajib form (`exception_reason` + photo), `is_radius_exception = true`, `status = pending_verification`
  - Last-day flexibility: check-out di kantor asal diizinkan setelah `duty_end_datetime`
- `app/Services/GoogleMapsService.php` — wrapper Google Distance Matrix API + Geocoding
- `app/Filament/Resources/AttendanceResource.php` + Pages:
  - `CreateAttendance` page: GPS capture, photo upload, geofencing validation
  - Check-out exception form

#### Multi-Session Rules
- Max 1 check-in per `attendance_request_id` per hari kalender
- Max 1 check-in per kantor biasa per hari kalender
- Constraint via unique index + model validation

#### Verification
```bash
# Test: check-in kantor biasa within radius
# Test: check-in kantor biasa outside radius (rejected)
# Test: check-in tugas luar approved request
# Test: check-in tugas luar pending request (rejected — tombol disabled)
# Test: check-out exception flow
# Test: early check-in > 90 menit (rejected)
# Test: late check-in > alfa_cutoff (Alfa status)
# Test: Google API fail, fallback Haversine, is_fallback = true
```

#### Commit
```
feat: GPS attendance service with geofencing, API fallback, and exception flow
```

---

### 2.4 — Daily Aggregation Scheduled Job

#### Files
- `app/Console/Commands/AggregateDailyAttendance.php`
  - Schedule: `dailyAt('23:59')` timezone `Asia/Jakarta`
  - Logic (blueprint Section 2A.5):
    1. **Prioritas 1:** Jika tanggal = `leave` atau `holiday` di `daily_attendance_summaries`, skip penalti
    2. **Prioritas 2:** Tugas luar terawal valid (`present`/`late`), override absen kantor biasa. `late_minutes` dari `duty_start_datetime`
    3. **Prioritas 3:** Kantor biasa (`present`/`late`/`missing_checkout`/`alfa`). `late_minutes` dari `check_in_time`
  - `check_in_id` dan `check_out_id` merujuk sesi yang MENANG prioritas
  - Pending verification check-out yang belum di-verify jadi `missing_checkout` (-5 poin)
- `app/Console/Commands/PopulateHolidaySummaries.php`
  - Saat `holidays` di-create/update, auto populate `daily_attendance_summaries` untuk semua active users
- Register di `routes/console.php`

#### Verification
```bash
php artisan attendance:aggregate --date=2026-07-25
# Verify daily_attendance_summaries terisi benar
# Test: user punya leave + attendance same day, leave menang
# Test: user punya tugas luar + kantor biasa same day, tugas luar menang
# Test: user punya pending_verification check-out, jadi missing_checkout
```

#### Commit
```
feat: daily attendance aggregation cron with priority hierarchy
```

---

### 2.5 — Holiday & Branch Office Management

#### Files
- [x] `app/Filament/Resources/HolidayResource.php` (CRUD)
- [x] `app/Filament/Resources/BranchOfficeResource.php` (CRUD + Google Maps widget)
- [x] `app/Filament/Resources/WorkScheduleResource.php` (CRUD)

#### Commit
```
feat: holiday, branch office, and work schedule management
```

---

## Phase 3: Layanan 2 & 3 — Merit System + Career (Minggu 3)

### Objective
Performance review, KPI scoring, attendance score calculation, merit grading, career paths, promotions.

---

### 3.1 — KPI Management

#### Files
- [x] `app/Filament/Resources/KpiResource.php` (CRUD master KPI)
- [x] `app/Filament/Resources/PerformanceReviewResource.php` + Pages
- [x] `app/Filament/Resources/ReviewKpiDetailResource.php` (inline di PerformanceReview)
- [x] Business logic di models:
  - [x] `ReviewKpiDetail`: auto-calc `subtotal_score = manager_score × weight / 100`
  - [x] `PerformanceReview`: cannot submit sebelum SEMUA `review_kpi_details` punya `manager_score` (mandatory completion constraint)
  - [x] Weight constraint: `Σ weight = 100` pada `review_kpi_details` sebelum status `submitted`
  - [x] Snapshot weight dari master `kpis` saat rapor dibuat

#### Verification
```bash
# Test: submit review tanpa semua manager_score terisi (rejected)
# Test: weight total != 100 (rejected)
# Test: subtotal_score auto-calculated
```

#### Commit
```
feat: KPI management with mandatory completion and weight constraints
```

---

### 3.2 — Attendance Score Calculation

#### Files
- `app/Services/AttendanceScoreService.php`
  - Input: `user_id`, `start_date`, `end_date`
  - Formula (blueprint Section 2B.2):
    ```
    Tanggal Mulai Hitung = max(start_date, join_date)
    Hari Kerja Efektif = Total Hari - Weekend - Holidays - Approved Leave
    n_alfa = max(0, Hari Kerja Efektif - n_hadir)
    Attendance Score = max(0, 100 - (2×n_late + 5×n_missing_checkout + 10×n_alfa))
    ```
  - Query `daily_attendance_summaries` untuk `n_hadir`, `n_late`, `n_missing_checkout`
  - Query `holidays` untuk count
  - Query `leave_requests` approved untuk leave days (handle partial overlap)

#### Verification
```bash
# Test: user hadir penuh, score = 100
# Test: user 3x telat, score = 94
# Test: user 2x alfa, score = 80
# Test: user join mid-period, effective start adjusted
```

#### Commit
```
feat: attendance score calculation service
```

---

### 3.3 — Final Merit Score & Grade

#### Files
- `app/Services/MeritScoreService.php`
  - Formula (blueprint Section 2B.3):
    ```
    Manager KPI Score = Σ(manager_score_i × weight_i / 100)
    Final Merit Score = (20% × Attendance Score) + (80% × Manager KPI Score)
    Grade: A(≥85), B(70-84), C(55-69), D(<55)
    ```
  - Stores result in `performance_reviews`: `attendance_score`, `manager_kpi_score`, `final_merit_score`, `grade`

#### Event Recalculation Triggers (blueprint Section 2B.3)
- `app/Listeners/RecalculateMeritOnChange.php`
  - Triggered by: `leave_requests` approval, `attendances` correction, `holidays` change
  - Find affected `performance_reviews` by date range overlap
  - Recalculate `attendance_score` dan `final_merit_score`
  - Update `grade` if threshold crossed
  - **Guard:** jika `performance_reviews.status = 'locked'`, reject auto-recalc, require forced recalculation authorization

#### Verification
```bash
# Test: calculate merit score for user with known data
# Test: approve leave retroactively, verify merit recalculated
# Test: locked period blocks recalculation
```

#### Commit
```
feat: merit score calculation with event-driven recalculation
```

---

### 3.4 — Career Paths & Promotions

#### Files
- `app/Filament/Resources/CareerPathResource.php` (CRUD)
- `app/Filament/Resources/PromotionResource.php` + Pages
- `app/Filament/Resources/IndividualDevelopmentPlanResource.php` + Pages
- `app/Services/ReadinessScoreService.php`
  - Formula (blueprint Section 2C.1):
    ```
    Readiness Score = (Σ min(current_level, min_required_level)) / (Σ min_required_level) × 100%
    ```
- `app/Console/Commands/ScanCandidatePool.php`
  - Monthly scan: 3 criteria (readiness ≥ 80%, grade ≥ min_merit_grade, experience ≥ min_experience_months)
  - Create `proposed` promotions for qualifying employees
- `app/Console/Commands/ExpireProposedPromotions.php`
  - Daily: `proposed` status > 30 hari = `expired`
  - `approved_by_hr` TIDAK expire
  - Next month: re-propose if still qualified

#### Dashboard Query
- Candidate Pool di Admin: hanya `proposed` status dalam 30 hari terakhir

#### Verification
```bash
php artisan career:scan-candidates
# Verify promotions created for qualifying employees
php artisan career:expire-promotions
# Verify only proposed > 30 days expired, approved_by_hr untouched
```

#### Commit
```
feat: career paths, promotions lifecycle, and candidate pool automation
```

---

## Phase 4: Panels, Dashboard, Polish (Minggu 4)

### Objective
2 Filament panels, role-based access, dashboard widgets, notifications, testing.

---

### 4.1 — Panel Architecture

#### Files
- `app/Providers/Filament/EmployeePanelProvider.php`
  - Path: `/app`
  - Mobile-first / PWA
  - Resources: Attendance (check-in/check-out), AttendanceRequest (bottom-up), LeaveRequest, ReviewKpiDetail (self-assessment), IndividualDevelopmentPlan, UserSkill
  - Widgets: attendance status today, upcoming duty, latest merit grade

- `app/Providers/Filament/AdminPanelProvider.php`
  - Path: `/admin`
  - Desktop-focused
  - **Role-based resource visibility:**
    - **Manager:** approve attendance requests, verify check-out exceptions, manager review KPI, propose promotions
    - **HR Admin:** master data CRUD (branch offices, work schedules, skills, positions, holidays), verify leave requests, monitor attendance recap, manage IDP, verify promotion documents
    - **Direksi:** executive dashboard, final sign-off promotions, merit allocation
    - **IT Admin:** user accounts, roles/permissions, API keys, system logs
  - Panel Switcher: employees who are also managers can switch panels

#### Commit
```
feat: two-panel architecture with role-based access control
```

---

### 4.2 — Dashboard Widgets

#### EmployeePanel Widgets
- `TodayAttendanceStatus` — status absen hari ini + tombol check-in/check-out
- `ActiveDutyTrips` — duty assignments aktif
- `LatestMeritGrade` — grade + score terakhir
- `IdpProgress` — IDP progress bars
- `CareerReadiness` — readiness score untuk target position

#### AdminPanel Widgets
- `HrAttendanceOverview` — rekap kehadiran per department (HR)
- `PendingApprovals` — pending leave + attendance requests (Manager)
- `CandidatePoolTable` — promotion candidates (HR/Direksi)
- `MeritDistribution` — grade distribution chart (Direksi)
- `AttendanceDropAlert` — alert jika attendance rate turun (HR)

#### Commit
```
feat: dashboard widgets for employee and admin panels
```

---

### 4.3 — Notifications

#### Port + Adapt
- WebPush infrastructure dari codebase lama (keep `HasPushSubscriptions`)
- New notifications:
  - `AttendanceRequestAssigned` — karyawan dapat tugas luar
  - `AttendanceRequestApproved` — bottom-up request approved
  - `LeaveRequestApproved` / `LeaveRequestRejected`
  - `CheckOutExceptionPending` — HR/Manager perlu verify
  - `MeritScorePublished` — karyawan lihat hasil merit
  - `PromotionProposed` — HR notification
  - `PromotionApproved` — karyawan notification

#### Commit
```
feat: notification system for all blueprint workflows
```

---

### 4.4 — Register Scheduled Jobs

#### `routes/console.php`
```php
Schedule::command('attendance:aggregate')->dailyAt('23:59')->timezone('Asia/Jakarta');
Schedule::command('career:scan-candidates')->monthlyOn(1, '00:30')->timezone('Asia/Jakarta');
Schedule::command('career:expire-promotions')->dailyAt('00:15')->timezone('Asia/Jakarta');
Schedule::command('db:backup')->dailyAt('02:00');
```

#### Commit
```
feat: register all scheduled jobs
```

---

### 4.5 — Testing

#### Priority Test Coverage
1. **Unit Tests:**
   - `AttendanceScoreServiceTest` — semua formula edge cases
   - `MeritScoreServiceTest` — scoring + grading
   - `ReadinessScoreServiceTest` — capped proportional formula
   - `GeoDistanceTest` — Haversine accuracy
   - Overlap validation (attendance requests vs leave requests)

2. **Feature Tests:**
   - Attendance check-in flow (kantor biasa + tugas luar)
   - Leave approval auto-creates daily summaries
   - Daily aggregation priority hierarchy
   - Merit recalculation triggers
   - Promotion lifecycle (propose → expire → re-propose)
   - Top-down auto-approve
   - Alfa cutoff enforcement

3. **Integration Tests:**
   - Full flow: create user, assign schedule, check-in, aggregate, calculate merit
   - Cross-module: leave + attendance overlap rejection

#### Verification
```bash
php artisan test
# All green
```

#### Commit
```
test: add unit and feature tests for all blueprint business rules
```

---

## Ringkasan Commit Timeline

| Phase | Commits | Estimasi |
|-------|---------|----------|
| Phase 0 | 1 commit (cleanup) | 1 hari |
| Phase 1 | 4 commits (enums, migrations, models, seeders) | 3-4 hari |
| Phase 2 | 5 commits (leave, attendance request, GPS service, aggregation, master CRUD) | 5-6 hari |
| Phase 3 | 4 commits (KPI, attendance score, merit, career) | 5-6 hari |
| Phase 4 | 5 commits (panels, widgets, notifications, cron, tests) | 5-6 hari |
| **Total** | **~19 commits** | **~20-22 hari kerja** |

---

## File Structure Final

```
app/
├── Console/Commands/
│   ├── AggregateDailyAttendance.php
│   ├── BackupDatabase.php
│   ├── ExpireProposedPromotions.php
│   └── ScanCandidatePool.php
├── Enums/
│   ├── AttendanceRequestStatus.php
│   ├── AttendanceStatus.php
│   ├── AttendanceType.php
│   ├── DailySummaryStatus.php
│   ├── FlowType.php
│   ├── IdpStatus.php
│   ├── LeaveStatus.php
│   ├── LeaveType.php
│   ├── PromotionStatus.php
│   ├── ReviewStatus.php
│   └── UserRole.php
├── Exceptions/
│   └── BusinessRuleException.php
├── Filament/
│   ├── Resources/
│   │   ├── AttendanceRequestResource.php
│   │   ├── AttendanceResource.php
│   │   ├── BranchOfficeResource.php
│   │   ├── CareerPathResource.php
│   │   ├── DepartmentResource.php
│   │   ├── HolidayResource.php
│   │   ├── IndividualDevelopmentPlanResource.php
│   │   ├── KpiResource.php
│   │   ├── LeaveRequestResource.php
│   │   ├── PerformanceReviewResource.php
│   │   ├── PositionResource.php
│   │   ├── PromotionResource.php
│   │   ├── SkillResource.php
│   │   ├── UserResource.php
│   │   └── WorkScheduleResource.php
│   └── Widgets/
│       ├── ActiveDutyTrips.php
│       ├── AttendanceDropAlert.php
│       ├── CandidatePoolTable.php
│       ├── CareerReadiness.php
│       ├── HrAttendanceOverview.php
│       ├── IdpProgress.php
│       ├── LatestMeritGrade.php
│       ├── MeritDistribution.php
│       ├── PendingApprovals.php
│       └── TodayAttendanceStatus.php
├── Listeners/
│   └── RecalculateMeritOnChange.php
├── Models/
│   ├── Attendance.php
│   ├── AttendanceRequest.php
│   ├── BranchOffice.php
│   ├── CareerPath.php
│   ├── DailyAttendanceSummary.php
│   ├── Department.php
│   ├── Holiday.php
│   ├── IndividualDevelopmentPlan.php
│   ├── Kpi.php
│   ├── LeaveRequest.php
│   ├── PerformanceReview.php
│   ├── Position.php
│   ├── PositionSkill.php
│   ├── Promotion.php
│   ├── ReviewKpiDetail.php
│   ├── User.php
│   ├── UserSkill.php
│   └── WorkSchedule.php
├── Notifications/
│   ├── AttendanceRequestApproved.php
│   ├── AttendanceRequestAssigned.php
│   ├── CheckOutExceptionPending.php
│   ├── LeaveRequestApproved.php
│   ├── LeaveRequestRejected.php
│   ├── MeritScorePublished.php
│   ├── PromotionApproved.php
│   └── PromotionProposed.php
├── Providers/
│   └── Filament/
│       ├── AdminPanelProvider.php
│       └── EmployeePanelProvider.php
├── Services/
│   ├── AttendanceScoreService.php
│   ├── AttendanceService.php
│   ├── GoogleMapsService.php
│   ├── MeritScoreService.php
│   └── ReadinessScoreService.php
└── Support/
    └── GeoDistance.php
```

---

> **Dokumen ini adalah execution plan. Blueprint [SOMETHING_NEW.md](./SOMETHING_NEW.md) tetap menjadi single source of truth untuk semua keputusan bisnis dan skema database.**
