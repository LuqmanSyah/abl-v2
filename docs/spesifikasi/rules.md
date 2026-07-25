# Rules — ABL Sistem SDM

## Coding Conventions

### PHP
- **No docblock comments** on methods/properties — use type hints only
- **No inline comments** in business logic — code expresses intent; comment only when explaining WHY (non-obvious domain rule)
- **Strict types** `declare(strict_types=1)` — used in all new files
- **Named arguments** for 3+ params
- **Arrow functions** for simple closures
- **Constructor property promotion** where possible
- **Enum labels** via `label()` method returning Bahasa Indonesia string

### Naming
| Thing | Convention | Example |
|-------|-----------|---------|
| Classes | PascalCase | `MeritCalculator` |
| Methods/Functions | camelCase | `calculate()`, `verifyByManager()` |
| Properties | camelCase | `$managerVerifiedAt` |
| DB tables | snake_case plural | `merit_results`, `duty_trips` |
| DB columns | snake_case | `review_period_id`, `calculated_at` |
| Route paths | kebab-case | `/pegawai/dinas/{dutyTrip}/absensi` |
| Enums | PascalCase | `UserRole`, `AttendanceStatus` |
| Filament resources | PascalCase | `MeritResultResource` |
| Migrations | descriptive prefix | `2026_07_15_020000_create_merit_tables.php` |

### Database
- **Timestamps** on all tables (`created_at`, `updated_at`)
- **Foreign keys** — `constrained()` with explicit onDelete (cascade/nullOnDelete/restrictOnDelete)
- **Unique constraints** for business-unique combos (not surrogate ID based)
- **Index** foreign keys + frequently queried columns
- **No soft deletes** — use FK cascade + audit log
- **JSON columns** for flexible config (`notification_preferences`, `approval_chains.steps`)

### Validation Pattern
```
BusinessRuleException → caught by AppServiceProvider `on('exception')` → Filament Notification::make()
```
- **Never** use `ValidationException` for business rules
- **Never** return `redirect()->back()->withErrors()` in services
- **Always** validate in model `booted()` or service methods
- **Filament forms** use `->numeric()->minValue()->maxValue()` for input validation

## Architecture Rules

1. **Services are stateless** — inject via container; no static state
2. **Controllers are thin** — validation + delegate to service; no business logic
3. **Models own business rules** — in `booted()` (saving/creating/updating/deleting)
4. **Enums describe domain states** — not strings or integers
5. **Traits for cross-cutting** — `HasWorkflow` (transactional state machine), not base class
6. **Notifications use traits** — `HasDynamicChannels` for channel resolution
7. **Tests mirror business flow** — arrange → act → assert in business language

## Filament Conventions

| Pattern | Standard |
|---------|----------|
| Form schema | Static `configure(Schema $schema)` method in `Schemas/` subdir |
| Table schema | Static `configure(Table $table)` method in `Tables/` subdir |
| Resource structure | 1 resource file + `Schemas/` + `Tables/` + `Pages/` |
| Navigation groups | 5 groups max; consistent label across panels |
| Actions | `Action::configureUsing()` in AppServiceProvider — modal alignment, width |
| Labels | Bahasa Indonesia (`label: 'Nama'`, `helperText: '...'`) |

## Git & Commits

- **Conventional Commits**: `type(scope): description`
  - Types: `feat`, `fix`, `refactor`, `docs`, `test`, `chore`
  - Scopes: `merit`, `attendance`, `career`, `ui`, `infra`, etc.
- **PRs**: squash-merge; single commit per feature/fix
- **Branch**: feature branches from `main`; merge via PR
- **Commit body**: bullet points of what changed and why

## Test Rules

- **Feature tests** for each module; no unit tests for Eloquent models
- **RefreshDatabase** trait on all feature tests
- **One assertion group per test** — multiple assertions for same business outcome OK
- **Factory + seeder** for test data; avoid hardcoded IDs
- **Coverage target** — all business rules in `booted()` + service layer
- **Run before commit**: `composer test` (config:clear + phpunit)

## Security

- **No secrets in code** — env vars for API keys, DB creds, VAPID keys
- **CSRF** on all POST routes (Laravel default)
- **SQL injection** — Eloquent parameterized queries
- **XSS** — Blade auto-escape (`{{ }}`)
- **CSV injection** — League\Csv handles escaping
- **Photo access** — scoped: owner, their manager, HR
- **Throttle** — login (5/min), attendance store (10/min), photo (20/min)
- **Rate limiting** — `throttle:10,1` on attendance POST route
