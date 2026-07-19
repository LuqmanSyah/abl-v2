# Progress Perbaikan — ABL Sistem SDM

## Fase 0: Fix Bug Kritis

| # | Bug | Severity | Status | Catatan |
|---|-----|----------|--------|---------|
| 1 | B7 — No throttle attendance | Critical | ✅ | `routes/web.php` tambah `throttle:10,1` |
| 2 | B3 — Score 0-255 tanpa validasi | High | ✅ | `PerformanceReview.php` tambah guard score 1-5 |
| 3 | B10 — guardManager tidak fresh read | High | ✅ | HasWorkflow trait sdh handle lockForUpdate |
| 4 | B6 — captured_at masa depan valid | Medium | ✅ | `AttendanceRecorder.php` tambah `isFuture()` check |
| 5 | B8 — CSV injection parsial | Medium | ✅ | `HrReportController.php` refactor pake League\Csv + formatter |
| 6 | B9 — Double inactive check | Low | ✅ | `AuthenticatedSessionController.php` hapus duplikasi |

## Fase 1: CI/CD + Test Coverage

| Item | Status | Catatan |
|------|--------|---------|
| GitHub Actions tests | ✅ | `.github/workflows/tests.yml` |
| DutyTripManagementTest | ✅ | 6 test — CRUD, scope, cancel |
| TrainingWorkflowTest | ✅ | 7 test — request, approve, reject, resubmit, HR verify |
| MentoringWorkflowTest | ✅ | 7 test — request, schedule, reject, complete, auth |
| HRReportTest | ✅ | Existing 3 test (OperationsReportTest) |
| NotificationDeliveryTest | ⏳ | Perlu setup mock notification channel |
| Sentry error monitoring | ✅ | `sentry/sentry-laravel` installed, env var added |

## Fase 2: Branding & UI Polish

| Item | Status | Catatan |
|------|--------|---------|
| Filament custom theme | ✅ | portal-filament.css sdh ada + dark mode style |
| Login page refine | ✅ | Favicon + brand logo component |
| Dark mode | ✅ | `darkMode(true, true)` di panel provider |
| Favicon + app icon | ✅ | SVG icons + favicon reference |

## Fase 3: PWA Production

| Item | Status | Catatan |
|------|--------|---------|
| SW hardening | ✅ | Cache all 3 panels, stale-while-revalidate |
| manifest.json | ✅ | Scope `/`, SVG icons, all panels |
| Push notification test | ✅ | VAPID + SW push listener + notificationclick |

## Fase 4: Mobile Optimization

| Item | Status | Catatan |
|------|--------|---------|
| Capture page audit | ✅ | viewport-fit=cover, responsive ≤560px, playsinline |
| Viewport + touch targets | ✅ | touch-action: manipulation, min-height 51px |
| Face-api model caching | ⏳ | Deferred — model already cached by SW |

## Fase 5: Backup & Operasional

| Item | Status | Catatan |
|------|--------|---------|
| MySQL backup command | ✅ | `app/Console/Commands/BackupDatabase.php` |
| Scheduled backup | ✅ | `routes/console.php` — daily 02:00 via `db:backup` |
| Ops docs update | ✅ | `docs/operations.md` — covers mysqldump + S3 |

## Fase 6: User Documentation

| Item | Status | Catatan |
|------|--------|---------|
| Pegawai guide | ✅ | `docs/panel-pegawai.md` — 210 lines |
| Atasan guide | ✅ | `docs/panel-atasan.md` — 224 lines |
| HR guide | ✅ | `docs/panel-hr.md` — 342 lines |
| Quick reference card | ⏳ | Not requested — existing docs cover all roles |
