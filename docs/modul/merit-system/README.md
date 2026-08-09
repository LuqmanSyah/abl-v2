# Merit System

Sistem penilaian kinerja periodik yang menggabungkan KPI, kepatuhan dinas, penilaian Atasan, dan umpan balik rekan menjadi skor akhir serta simulasi bonus.

## Alur

```
ReviewPeriod dibuat (bobot: KPI, kepatuhan dinas, Atasan, rekan)
  ↓
HR assign KPI indicator per periode
  ↓
Manager input target & capaian KPI per pegawai
  ↓
Review: Atasan→Pegawai + Rekan→Pegawai
  ↓
`merit:calculate` — hitung skor tiap komponen
  ↓
Manager verifikasi → HR publish
  ↓
Hasil dipakai untuk rekomendasi pelatihan dan simulasi bonus
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

### Skor Kepatuhan Dinas
```
totalDays = jumlah tanggal unik dari dinas selesai dalam periode
validDays = jumlah tanggal unik dengan absensi Valid dalam periode
score     = min(validDays / totalDays × 100, 100)
```

### Review Scores
```
managerScore = avg(Atasan→Pegawai) / 5 × 100
peerScore    = avg(Rekan→Pegawai) / 5 × 100
```

### Total Score
```
total = (kpi × weight_kpi + duty × weight_duty
       + manager × weight_manager + peer × weight_peer) / 100
```

### Bonus
```
simulasi_bonus = base_bonus × total / 100
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

## Test

- [Testing web](testing-web.md)
- Automated test: `tests/Feature/MeritSystemTest.php` dan `tests/Feature/FlowTest.php`.
