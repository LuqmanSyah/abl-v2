# Merit System

Sistem penilaian kinerja periodik — menggabungkan KPI, kedisiplinan, penilaian atasan, dan 360 review menjadi skor akhir + estimasi bonus.

## Alur

```
ReviewPeriod dibuat (bobot: KPI, disiplin, atasan, 360)
  ↓
HR assign KPI indicator per periode
  ↓
Manager input target & capaian KPI per pegawai
  ↓
Review: Manager→Employee + Employee→Manager + Peer
  ↓
`merit:calculate` — hitung skor tiap komponen
  ↓
Manager verifikasi → HR publish
  ↓
Hasil dipakai untuk rekomendasi pelatihan & bonus
```

## Komponen

| Layer | File | Fungsi |
|-------|------|--------|
| **Service** | `app/Services/MeritCalculator.php` | Kalkulasi skor: KPI, disiplin, review, total, bonus |
| **Model** | `app/Models/ReviewPeriod.php` | Periode merit dgn bobot tiap komponen |
| **Model** | `app/Models/MeritResult.php` | Hasil merit per pegawai per periode |
| **Model** | `app/Models/EmployeeKpi.php` | Target & capaian KPI per indikator |
| **Model** | `app/Models/KpiIndicator.php` | Definisi indikator KPI per periode |
| **Model** | `app/Models/PerformanceReview.php` | Review score (1-5) — immutable setelah submit |
| **Command** | `app/Console/Commands/CalculateMerit.php` | `merit:calculate --period=N` |
| **Enum** | `app/Enums/ReviewType.php` | ManagerToEmployee, EmployeeToManager, Peer |
| **Enum** | `app/Enums/AttendanceStatus.php` | Dipakai untuk skor disiplin |

## Formula Skor

### KPI Score
```
ratio = min(achievement / target, 1.2)  // cap 120%
kpiScore = sum(ratio × weight) / totalWeight × 100
```

### Discipline Score
```
totalDays  = sum(day count) dari semua trip dlm periode
validDays  = sum(attendance Valid) dlm periode
discipline = min(validDays / totalDays × 100, 100)
```

### Review Scores
```
managerScore  = avg(Manager→Employee score) / 5 × 100
review360Score = avg(Employee→Manager + Peer score) / 5 × 100
```

### Total Score
```
total = (kpi × weight_kpi + discipline × weight_disc
       + manager × weight_mgr + review360 × weight_360) / 100
```

### Bonus
```
bonus = base_bonus × total / 100
```

## Workflow Verifikasi

```
[Calculated] → Manager verify → [ManagerVerified] → HR verify → [Published]
     ↑               ↓                    ↓                      ↓
  Re-kalkulasi    Lock skor           Lock period            Immutable
  (update)        Tidak bisa          KPI/indikator          Semua data
                 dihitung ulang       tidak bisa diubah       terkunci
```

## Aturan Bisnis

| Aturan | Implementasi |
|--------|-------------|
| Bobot wajib 100% | `ReviewPeriod::booted` — `$total !== 100` |
| KPI target > 0 | `EmployeeKpi::booted` |
| KPI achievement ≥ 0 | `EmployeeKpi::booted` |
| Review score 1-5 | `PerformanceReview::booted` |
| Review immutable | `PerformanceReview::booted` — block update/delete |
| Merit tidak bisa dihitung ulang setelah verifikasi | `MeritCalculator:28` |
| KPI tidak bisa diubah setelah publish | `EmployeeKpi::hasPublishedMeritResult()` |
| Periode tidak bisa diubah setelah publish | `ReviewPeriod::hasPublishedMeritResults()` |

## Bug Fixes

| # | Bug | Status | Fix |
|---|-----|--------|-----|
| 1 | OR query tanpa grouping — non-Employee ikut | ✅ | Wrap OR dlm `where(fn)` group |
| 2 | Attendance luar periode ikut hitung | ✅ | Filter `whereBetween('captured_at')` |
| 3 | Trip mulai sebelum periode tidak masuk | ✅ | Overlap query |
| 4 | N+1 query attendance | ✅ | Eager load `with()` |
| 5 | MeritResult bisa dibuat manual | ⏳ | Perlu `booted()` guard |
| 6 | DomainException silent skip | ⏳ | Log level `warning` |
| 7 | Integer cast bobot pecahan | ⏳ | Float cast / toleransi |

## Test

| File | Jumlah |
|------|--------|
| `tests/Feature/MeritSystemTest.php` | 11 test |
| `tests/Feature/FlowTest.php` | 1 test (end-to-end) |
