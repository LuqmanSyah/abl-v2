# Design — ABL Sistem SDM

## UI Philosophy

- **Filament native** — minimal custom CSS; leverage Filament 5 components, schemas, and theming
- **Bahasa Indonesia** — all labels, helper text, notifications, and error messages
- **Mobile-first** for attendance capture; desktop-optimized for management panels
- **Dark mode** enabled via `darkMode(true, true)` — toggle accessible per user session

## Panel Identity

| Panel | Path | Color | Brand Name | Visual Cue |
|-------|------|-------|------------|------------|
| HR | `/hr` | Amber (#F59E0B) | Portal SDM/HR | Warm accent, full nav groups |
| Manager | `/atasan` | Green (#10B981) | Portal Atasan | Nature accent, team-focused |
| Employee | `/pegawai` | Blue (#3B82F6) | Portal Pegawai | Cool accent, personal scope |

### Brand Elements
- **Logo**: Blade component `components.brand-logo` — SVG icon
- **Favicon**: SVG `icons/icon-192.svg`
- **Avatar**: OrangeAvatarProvider — generates colored initials on orange background
- **Theme**: Custom CSS `portal-filament.css` for overrides

## Layout Patterns

### Dashboard Widgets Layout

| Panel | Widgets |
|-------|---------|
| HR | Stats (total employee/trip/attendance), Active Trips table, Attendance Stats, Merit Per Unit table, Drop Alert |
| Manager | Stats (team size/pending), Team Merit table, Pending Approvals, Team Trip table, Incomplete KPI table |
| Employee | Stats (my trips/kpis/merit), Latest Merit card, KPI Progress table, Active Trips table, Training+Mentoring table |

### Form Patterns
- **Modal forms** for create/edit (not full page) — configured via `Action::configureUsing()`
  - Modal alignment: `Start`
  - Modal width: `Large` (standard), `2xl` (CreateAction/EditAction), `Medium` (delete)
  - Sticky header + footer
- **Section grouping** with icons and descriptions
- **Inline toggles** for boolean preferences
- **Select with search + preload** for relation picks
- **Hidden fields** for auto-set values (manager_id, reviewer_id, type)

### Table Patterns
- **Default sort**: newest first (`->defaultSort('created_at', 'desc')`)
- **Searchable** columns for name/identity fields
- **Filters**: status, date range, employee scope
- **Record actions**: EditAction inline; DeleteBulkAction in toolbar
- **Toggleable columns** for timestamps (hidden by default)

## Merit Results Infolist

Dedicated infolist with 3 sections:

1. **Ringkasan Merit** (2 columns) — period, employee, total score (color-coded: ≥80 green, ≥60 yellow, <60 red), estimated bonus (IDR money format)
2. **Komponen Nilai** (4 columns) — KPI, discipline, manager, 360 scores each with weight suffix
3. **Status Verifikasi** (3 columns) — manager verifier + timestamp, hr verifier + timestamp, published_at

## Attendance Capture Page

### Layout (mobile-first)
```
[Camera Preview / Photo Upload]
[Action Button: "Ambil Absen"]
[Status Badge: lokasi, waktu, radius]
```

- `viewport-fit=cover` for notch displays
- `touch-action: manipulation` — no double-tap zoom
- Touch targets ≥51px min-height
- Responsive ≤560px breakpoint

### Flow
1. Preload face-api models via Service Worker cache
2. On camera capture → extract face descriptor in parallel with GPS
3. GPS check → distance calculation
4. Submission: photo + descriptor + coordinates + timestamp
5. Server validates radius, clock, mock location, face match
6. Response: status (Valid/NeedsReview/OutsideRadius/Late)

### Offline Support
- IndexedDB queue for offline submissions
- Auto-sync when online
- Network status badge indicator

## Notification UI

- **In-app (bell)** — polled every 30s, Filament database notifications
- **Web Push** — VAPID authenticated, SW `push` listener + `notificationclick` → open URL
- Service Worker: `public/sw.js` — static asset cache + push handling

## CSS Architecture

| Source | Purpose |
|--------|---------|
| Filament built-in | 95% of styling |
| `portal-filament.css` | Brand overrides, spacing tweaks, capture page styles |
| Blade inline | Conditional: PWA visibility, offline badge |

## PWA Manifest

- Scope: `/`
- Icons: SVG format (192px, 512px)
- Service Worker registered on all 3 panels via `pwa.register` blade partial
- Theme color: matches panel primary

## Map Integration

- **Leaflet + OpenStreetMap** (no API key needed)
- Custom Filament component: `MapPicker` — click-to-set coordinates
- Used in: DutyLocation form (admin), Trip form (auto-fill from location)
- Radius circle overlay on map for geofence visualization

## Typography & Spacing

- Font: system stack (Filament default)
- Labels: `14px` weight 800 (custom in capture page)
- Helper text: `13px` muted (`#64748b`)
- Modal widths: configurable via `Width` enum (Medium, Large, TwoExtraLarge)
