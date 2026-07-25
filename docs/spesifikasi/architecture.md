# Architecture — ABL Sistem SDM

## Tech Stack

| Layer | Stack |
|-------|-------|
| Framework | Laravel 12 (PHP ^8.2) |
| Admin Panel | Filament 5 (3 panels) |
| Database | MySQL (prod), SQLite (dev/test) |
| Frontend | Blade + Vite + vanilla JS |
| Maps | Leaflet + OpenStreetMap |
| CSV/XLSX | League\Csv, OpenSpout |
| PDF | barryvdh/laravel-dompdf |
| Notifications | DB, Web Push (VAPID), Email |
| Face Verification | @vladmandic/face-api (client), Python (server fallback) |
| Monitoring | Sentry |
| CI | GitHub Actions |

## Folder Structure

```
app/
├── Channels/           # Custom notification channels
├── Console/Commands/   # Artisan commands (merit:calculate, backup, purge)
├── Enums/              # Backed enums (UserRole, AttendanceStatus, etc.)
├── Exceptions/         # BusinessRuleException
├── Filament/
│   ├── AvatarProviders/
│   ├── Forms/Components/  # MapPicker custom component
│   ├── Pages/             # EditProfile (shared across panels)
│   ├── Resources/         # Per-module CRUD (subdir per resource)
│   └── Widgets/           # Stats, tables per panel
├── Http/
│   ├── Controllers/       # Attendance, Auth, Report, FaceVerify
│   └── Middleware/        # EnsureUserIsActive, HandleForbiddenPanelPage
├── Mail/
├── Models/             # Eloquent models
│   └── Concerns/       # Traits: HasDynamicChannels, HasWorkflow
├── Notifications/      # 9 notification classes
├── Providers/          # AppServiceProvider, Filament panel providers
├── Services/           # MeritCalculator, AttendanceRecorder, CareerGapService
└── Support/            # GeoDistance helper

config/                 # hr.php (attendance tolerance)
database/migrations/    # 16 migration files
docs/                   # Architecture, design, rules, modules
resources/views/        # auth, attendance, components, reports, pwa
routes/
├── web.php             # Auth + attendance + report routes
└── console.php         # Scheduled commands
tests/
├── Feature/            # 11 test files, 87 tests
└── Unit/               # SqliteBackup, Example
```

## Panel Architecture

3 Filament panels — role-based, shared base config via `RolePanelProvider`:

| Panel | Path | Brand | Primary Color | Resources |
|-------|------|-------|---------------|-----------|
| HR | `/hr` | Portal SDM/HR | Amber | 20 resources (Org, Kinerja, Pengembangan, Laporan) |
| Manager | `/atasan` | Portal Atasan | Green | 10 resources (Operasional, Kinerja, Pengembangan) |
| Employee | `/pegawai` | Portal Pegawai | Blue | 10 resources (Operasional, Kinerja, Pengembangan) |

### Panel Config (RolePanelProvider)
- Login redirect via shared `/login` route
- Profile page: `EditProfile` (nama, email, telepon, preferensi notif, password)
- Avatar: OrangeAvatarProvider (inicial based)
- Sidebar collapsible, unsaved changes alerts, DB notifications polling 30s
- Custom CSS: `portal-filament.css`
- Brand logo: blade component
- Middleware chain: HandleForbiddenPanelPage (redirect if wrong panel), CSRF, session, auth

## Service Layer

### Core Services
| Service | Responsibility |
|---------|---------------|
| `MeritCalculator` | KPI/discipline/review scoring, weighted total, bonus estimation |
| `AttendanceRecorder` | GPS validation, clock check, face comparison, status classification |
| `CareerGapService` | Competency gap analysis against target position |

### Pattern: BusinessRuleException
All business validation throws `BusinessRuleException` → caught by `AppServiceProvider` listener → Filament notification "Tindakan tidak dapat diproses". Never 500 for user errors.

## Notification Flow

```
Event (model observer / controller)
  → Notification class (via Notifiable trait)
  → HasDynamicChannels::resolveChannels() (filters by user prefs)
  → Channel: database, webpush, email
  → User sees: in-app bell, push notification, email
```

### 9 Notification Classes
| Class | Trigger | Recipients |
|-------|---------|------------|
| TripAssigned | DutyTrip created | Employee |
| AttendanceReminder | Scheduler 08:00/12:00 | Employee |
| AttendanceNeedsReview | Attendance flagged | HR |
| MentoringPending | Mentoring created | Manager |
| MentoringScheduled | Mentoring approved | Employee |
| MeritPublished | Merit HR-verified + published | Employee |
| MeritReadyForVerification | Merit calculated | Manager |
| KpiDeadlineReminder | Scheduler 09:00 | Manager |
| TrainingPending | TrainingRequest created | Manager |

## State Machines (Workflow)

Models use `HasWorkflow` trait — `workflowTransition()` reloads model in `lockForUpdate` transaction before mutating. Prevents race conditions.

| Model | States |
|-------|--------|
| TrainingRequest | PendingManager → PendingHr → Approved → Completed |
| | ↳ Rejected → (resubmit) → PendingManager |
| Mentoring | Pending → Approved → Completed |
| | ↳ Rejected |
| MeritResult | (calculated) → ManagerVerified → HrVerified+Published |

## Key Design Decisions

1. **No soft deletes** — cascade deletes with FK constraints; hard delete with audit log
2. **Scope filtering** — every model has `scopeVisibleTo(User)` returning query scoped by role/relation
3. **Immutable reviews** — `PerformanceReview::updating/deleting` throws; once submitted, score locked
4. **Published merit locks period** — `ReviewPeriod::saving` rejects changes if any published MeritResult exists
5. **Daily attendance** — multi-day trip → 1 attendance per calendar day; trip stays Approved until all days pass
6. **Delegation** — Manager can set `delegate_id`; delegate acts on their behalf (logged as `delegated`)
7. **Escalation** — pending approvals >3 days → daily scheduler notifies HR
