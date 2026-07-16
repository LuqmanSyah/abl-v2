# Revisi — Alur Rekomendasi Pelatihan oleh Atasan

---

## 1. Latar Belakang

Saat ini, pelatihan hanya dapat diajukan oleh Pegawai melalui menu **Pengajuan Pelatihan**.
Atasan hanya berperan menyetujui atau menolak. Pegawai belum memiliki akses melihat merit score
bawahannya dan belum bisa merekomendasikan pelatihan berdasarkan pencapaian merit.

Tujuan revisi: **Atasan dapat merekomendasikan pelatihan untuk pegawai berdasarkan merit score**.

---

## 2. Perubahan Alur Bisnis

### 2.1. Alur Lama

```
Employee → request → PendingManager → Manager approve → PendingHr → HR verify → Approved → HR complete → Completed
```

### 2.2. Alur Baru

```
Manager lihat merit pegawai (manual decide cukup)
  → Manager create TrainingRequest untuk employee via button "Rekomendasi Pelatihan"
    → status langsung Approved
      → Employee lihat di panel-nya
        → HR complete (setelah selesai)
```

**Poin penting:**
- Threshold merit tidak di-enforce oleh sistem. Atasan lihat score merit pegawai lalu decide manual.
- Manager create langsung `Approved` — tidak perlu manager approve step (karena dia yang buat).
- Tidak perlu verifikasi HR dulu. HR hanya complete setelah training selesai.
- Employee lihat training langsung di daftar "Pengajuan Pelatihan" miliknya.

---

## 3. Ringkasan Perubahan

### 3.1. Tidak Berubah

| Aspek | Status |
|-------|--------|
| Model kolom TrainingRequest | ✅ Pakai existing fields (`manager_id`, `status`, `manager_decided_at`) |
| Tabel database | ✅ Tidak ada migrasi baru |
| Employee panel | ✅ Tidak ada perubahan UI |
| HR panel | ✅ Tidak ada perubahan (tetap bisa `complete`) |
| TrainingRequestStatus enum | ✅ `Approved` sudah ada |
| ActivityLog | ✅ Cuma tambah action key baru `training.recommended` |
| Flow employee request | ✅ Tidak berubah — employee tetap bisa request sendiri |
| Existing tests | ✅ Tidak perlu rewrite |

### 3.2. Berubah

| # | File | Perubahan |
|---|------|-----------|
| 1 | `app/Models/TrainingRequest.php` | Modifikasi `booted()` creating handler — izinkan Manager create untuk bawahan. Tambah method `recommendByManager()`. |
| 2 | `app/Filament/Resources/TrainingRequests/TrainingRequestResource.php` | (Manager panel) Tambah halaman create khusus Manager untuk rekomendasi, atau custom action di halaman list karyawan. |
| 3 | `app/Providers/Filament/ManagerPanelProvider.php` | Pastikan resource TrainingRequest terdaftar. |
| 4 | `tests/Feature/CareerDevelopmentTest.php` | Tambah test untuk flow rekomendasi atasan. |

---

## 4. Detail Implementasi

### 4.1. Model — `app/Models/TrainingRequest.php`

**Modifikasi `booted()`:**

```php
static::creating(function (self $request): void {
    $request->status ??= TrainingRequestStatus::PendingManager;
    $request->requested_at ??= now();

    // Employee hanya bisa buat untuk dirinya sendiri
    if (auth()->user()?->role === UserRole::Employee && $request->user_id !== auth()->id()) {
        throw new DomainException('Pegawai hanya dapat mengajukan pelatihan untuk dirinya sendiri.');
    }

    // Validasi relasi: user_id adalah Employee, manager_id adalah Manager, training aktif
    if (User::whereKey($request->user_id)->where('role', UserRole::Employee)->where('manager_id', $request->manager_id)->doesntExist()
        || User::whereKey($request->manager_id)->where('role', UserRole::Manager)->doesntExist()
        || Training::whereKey($request->training_id)->where('is_active', true)->doesntExist()) {
        throw new DomainException('Pengajuan pelatihan tidak valid.');
    }
});
```

**Method baru:**

```php
public static function recommendByManager(
    User $manager,
    User $employee,
    Training $training,
    string $reason,
): self {
    // Validasi
    throw_unless(
        $manager->role === UserRole::Manager
        && $employee->manager_id === $manager->id
        && $employee->role === UserRole::Employee,
        DomainException::class,
        'Hanya Atasan yang dapat merekomendasikan pelatihan untuk bawahannya.',
    );

    throw_unless(
        $training->is_active,
        DomainException::class,
        'Pelatihan tidak aktif.',
    );

    $request = static::create([
        'user_id' => $employee->id,
        'training_id' => $training->id,
        'manager_id' => $manager->id,
        'status' => TrainingRequestStatus::Approved,
        'reason' => $reason,
        'requested_at' => now(),
        'manager_decided_at' => now(),
    ]);

    ActivityLog::record('training.recommended', $request, $manager);

    return $request;
}
```

### 4.2. Manager Panel — Custom Action

**Di halaman daftar pegawai (atau halaman KPI Pegawai):**

Tambah tombol "Rekomendasi Pelatihan" per baris pegawai yang merupakan bawahan langsung.

**Modal form:**
- Employee (readonly, dari row context)
- Current merit score (readonly, dari `meritResults()->latest()->first()?->total_score`)
- Training (select dari katalog aktif)
- Reason (textarea)

**Submit →** `TrainingRequest::recommendByManager($manager, $employee, $training, $reason)`

Alternatif: buat halaman create di resource `TrainingRequests` panel Manager dengan field employee (filtered ke bawahan), training, reason.

### 4.3. Employee Panel — Tanpa Perubahan

Employee lihat training `Approved` di list "Pengajuan Pelatihan" seperti biasa.
Resource `TrainingRequestResource` scope `visibleTo()` sudah handle.

### 4.4. HR Panel — Tanpa Perubahan

HR tetap bisa `complete()` training yang sudah `Approved`.

---

## 5. Files yang Kena Dampak

```
app/Models/TrainingRequest.php           — +recommendByManager(), edit booted()
app/Filament/Resources/TrainingRequests/
    ├── TrainingRequestResource.php      — +halaman create (Manager custom)
    ├── Pages/
    │   ├── ListTrainingRequests.php     — +custom action (opsional)
    │   └── CreateTrainingRequest.php    — +form untuk manager (baru)
    └── Schemas/
        └── TrainingRequestForm.php      — +schema baru untuk manager
tests/Feature/CareerDevelopmentTest.php  — +test rekomendasi atasan
```

---

## 6. Test Plan

| # | Skenario | Langkah | Ekspektasi |
|---|----------|---------|------------|
| 1 | Manager rekomendasi training — valid | Manager buat TrainingRequest untuk bawahan, training aktif | Status `Approved`, `manager_decided_at` terisi, activity log tercatat |
| 2 | Manager rekomendasi — bukan bawahan | Manager create untuk employee bukan bawahannya | Error |
| 3 | Manager rekomendasi — training nonaktif | Pilih training dengan `is_active=false` | Error |
| 4 | Employee lihat rekomendasi | Employee login, buka daftar training request | Training rekomendasi muncul |
| 5 | HR complete training rekomendasi | HR complete training yg direkomendasi | Status `Completed` |
| 6 | Employee request sendiri — masih jalan | Employee create request sendiri | Status `PendingManager` (tidak berubah) |

---

## 7. Urutan Implementasi

```
1. Model: Tambah recommendByManager() + edit creating handler
2. Test: Tulis test untuk flow baru dulu (TDD)
3. Filament: Buat halaman/action untuk Manager panel
4. Test: Run all tests → green
5. Manual test: Simulasi 3 role di browser
```

---

## 8. Rumus Merit — Referensi Pertimbangan Karir

### 8.1. Formula Lengkap

Bersumber dari `app/Services/MeritCalculator.php:31-58`.
Dibuktikan hitungan eksak di `tests/Feature/MeritSystemTest.php:52-57`.

#### 8.1.1. KPI Score (0-120, bobot configurable default 40%)

```
Untuk setiap KPI indicator i:
  rasio_i      = min(achievement_i / max(target_i, 0.01), 1.2)
  score_i      = rasio_i × weight_i

kpi_score = (Σ score_i) / (Σ weight_i) × 100
```

**Sumber data:** `employee_kpis.target`, `employee_kpis.achievement`, `kpi_indicators.weight`

**Penanggung jawab input:** Atasan

**Contoh:**
```
Indicator A: target=100, achievement=100, weight=60
  → rasio = min(100/100, 1.2) = 1.0
  → score = 1.0 × 60 = 60

Indicator B: target=100, achievement=50, weight=40  
  → rasio = min(50/100, 1.2) = 0.5
  → score = 0.5 × 40 = 20

kpi_score = (60 + 20) / (60 + 40) × 100 = 80
```

#### 8.1.2. Discipline Score (0-100, bobot default 20%)

```
Duty trips dalam range periode:
  status = Completed ATAU (Approved DAN ends_at <= now)

discipline_score = valid_attendance_count / total_duty_trip_count × 100
  Jika total_duty_trip_count = 0 → discipline_score = 100
```

**Sumber data:** `duty_trips.status`, `attendances.status`

**Penanggung jawab input:** Otomatis dari sistem absensi

**Contoh:**
```
Total duty trips periode = 2 (1 Completed + hadir, 1 Approved + tidak hadir)
Valid attendance = 1

discipline_score = 1 / 2 × 100 = 50
```

#### 8.1.3. Manager Score (0-100, bobot default 20%)

```
manager_score = AVG(score) / 5 × 100
  Jika tidak ada review → 0
```

**Sumber data:** `performance_reviews` type `manager_to_employee`

**Penanggung jawab input:** Atasan (nilai 1-5)

**Contoh:**
```
Score dari atasan = 4

manager_score = 4 / 5 × 100 = 80
```

#### 8.1.4. 360 Review Score (0-100, bobot default 20%)

```
review_360_score = AVG(score) / 5 × 100
  Jika tidak ada review → 0
```

**Sumber data:** `performance_reviews` type `employee_to_manager` ATAU `peer`

**Penanggung jawab input:** Rekan kerja / bawahan (nilai 1-5)

**Contoh:**
```
Score dari rekan = 3

review_360_score = 3 / 5 × 100 = 60
```

#### 8.1.5. Total Score (0-100)

```
total_score = (
    kpi_score × kpi_weight
  + discipline_score × discipline_weight
  + manager_score × manager_weight
  + review_360_score × review_360_weight
) / 100
```

**Sumber data:** Bobot dari `review_periods`, nilai dari perhitungan di atas

**Contoh (bobot default 40/20/20/20):**
```
total = (80×40 + 50×20 + 80×20 + 60×20) / 100
      = (3200 + 1000 + 1600 + 1200) / 100
      = 7000 / 100
      = 70
```

#### 8.1.6. Estimated Bonus

```
estimated_bonus = base_bonus × total_score / 100
```

**Sumber data:** `review_periods.base_bonus`

**Contoh:**
```
base_bonus = 1_000_000, total_score = 70

estimated_bonus = 1_000_000 × 70 / 100 = 700_000
```

### 8.2. Data Flow Diagram

```
INPUT                              KOMPONEN                    OUTPUT
─────────────────────────────────────────────────────────────────────
employee_kpis.target         ─┐
employee_kpis.achievement    ─┤──→ KPI Score (0-120)          
kpi_indicators.weight        ─┘                                   
                                                                    
duty_trips.status            ─┐──→ Discipline Score (0-100)    
attendances.status           ─┘                                   
                                                            ──→ TOTAL SCORE (0-100)
performance_reviews.score    ─┐──→ Manager Score (0-100)       
(type=manager_to_employee)   ─┘                                   
                                                                    
performance_reviews.score    ─┐──→ 360 Review Score (0-100)    
(type=employee_to_manager    ─┘                                   
 ATAU peer)                                                       
                                                                    
review_periods.base_bonus    ──→ Estimated Bonus (Rp)          
```

### 8.3. Nilai Eksak dari Test (Verifikasi)

Dari `tests/Feature/MeritSystemTest.php`:

| Komponen | Hasil | Rumus |
|----------|-------|-------|
| kpi_score | 80.00 | (60+20)/(60+40)×100 |
| discipline_score | 50.00 | 1/2×100 |
| manager_score | 80.00 | 4/5×100 |
| review_360_score | 60.00 | 3/5×100 |
| total_score | **70.00** | (80×40+50×20+80×20+60×20)/100 |
| estimated_bonus | **700.000** | 1.000.000 × 70/100 |

---

## 9. Audit Trail untuk Manager Decision

Saat modal "Rekomendasi Pelatihan" dibuka, manager perlu lihat **bukan hanya angka jadi** tapi juga **riwayat perubahan** tiap komponen.

### 9.1. Data yang Ditampilkan di Modal

```
┌─────────────────────────────────────────────────────────┐
│  REKOMENDASI PELATIHAN — Pegawai Demo 1                 │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Periode Terakhir: Semester 1 2026                      │
│  TOTAL SCORE: 84.00                                   │
│                                                         │
│  ┌───── KOMPONEN ─────┬───── NILAI ────┬─ DETAIL ──┐   │
│  │ KPI                 │ 85.00 (×40%)   │ 🔍 Lihat  │   │
│  │ Disiplin            │ 92.00 (×20%)   │ 🔍 Lihat  │   │
│  │ Manager Review      │ 78.00 (×20%)   │ 🔍 Lihat  │   │
│  │ 360 Review          │ 80.00 (×20%)   │ 🔍 Lihat  │   │
│  ├─────────────────────┼────────────────┼────────────┤   │
│  │ EST. BONUS          │ Rp 840.000     │            │   │
│  └─────────────────────┴────────────────┴────────────┘   │
│                                                         │
│  ┌─ KPI DETAIL ───────────────────────────────────────┐ │
│  │ Indicator       │ Target │ Capaian │ Score │ Bobot │ │
│  │ Kualitas        │ 100    │ 100     │ 100   │ 60%   │ │
│  │ Kecepatan       │ 100    │ 50      │ 50    │ 40%   │ │
│  │─────────────────┼────────┼─────────┼───────┼───────│ │
│  │ Riwayat perubahan KPI:                              │ │
│  │ 15 Jul 2026 08:00 — Dibuat oleh Atasan Demo        │ │
│  │ 16 Jul 2026 10:30 — Capaian diubah: 40 → 50       │ │
│  └───────────────────────────────────────────────────┘ │
│                                                         │
│  ┌─ REVIEW 360 DETAIL ───────────────────────────────┐ │
│  │ Penilai        │ Tipe        │ Score │ Tanggal     │ │
│  │ Atasan Demo    │ Atasan→Pgw  │ 4     │ 15 Jul 2026 │ │
│  │ Pegawai Demo 2 │ Peer        │ 3     │ 15 Jul 2026 │ │
│  └───────────────────────────────────────────────────┘ │
│                                                         │
│  ┌─ DISIPLIN DETAIL ─────────────────────────────────┐ │
│  │ Tujuan Dinas      │ Tanggal    │ Status Absensi    │ │
│  │ Kunjungan Kerja 1 │ 01 Aug 26  │ Valid ✅           │ │
│  │ Kunjungan Kerja 3 │ 03 Aug 26  │ Outside Radius ⚠️ │ │
│  └───────────────────────────────────────────────────┘ │
│                                                         │
│  ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ │
│  Pegawai: [Pegawai Demo 1 — readonly]                  │
│  Pelatihan: [▼ Pilih training...]                       │
│  Alasan: [...........................]                  │
│                                                         │
│  [BATAL]                              [REKOMENDASI]     │
└─────────────────────────────────────────────────────────┘
```

### 9.2. Sumber Data Audit Trail

| Komponen | Riwayat dari | Tabel |
|----------|-------------|-------|
| KPI target/achievement | `activity_logs` action `kpi.*` + `employee_kpis.updated_at` | `employee_kpis`, `activity_logs` |
| Manager review | `performance_reviews` (immutable setelah submit) | `performance_reviews` |
| 360 review | `performance_reviews` (immutable setelah submit) | `performance_reviews` |
| Attendance status | `attendances.status` | `attendances` |
| Merit publish | `activity_logs` action `merit.*` | `activity_logs` |

### 9.3. Implementasi — Method di Model

**`app/Models/User.php` — method baru:**

```php
public function meritBreakdownForManager(?ReviewPeriod $period = null): array
{
    $period ??= ReviewPeriod::where('is_active', true)->latest('starts_at')->first();
    if (! $period) return [];

    $result = $this->meritResults()->where('review_period_id', $period->id)->first();

    return [
        'period' => $period->name,
        'kpi_score' => $result?->kpi_score,
        'discipline_score' => $result?->discipline_score,
        'manager_score' => $result?->manager_score,
        'review_360_score' => $result?->review_360_score,
        'total_score' => $result?->total_score,
        'estimated_bonus' => $result?->estimated_bonus,
        'kpi_details' => EmployeeKpi::with('indicator')
            ->where('employee_id', $this->id)
            ->where('review_period_id', $period->id)
            ->get()
            ->map(fn ($kpi) => [
                'indicator' => $kpi->indicator->name,
                'target' => $kpi->target,
                'achievement' => $kpi->achievement,
                'weight' => $kpi->indicator->weight,
            ]),
        'reviews' => PerformanceReview::with('reviewer')
            ->where('reviewee_id', $this->id)
            ->where('review_period_id', $period->id)
            ->get()
            ->map(fn ($review) => [
                'reviewer' => $review->reviewer->name,
                'type' => $review->type->label(),
                'score' => $review->score,
                'submitted_at' => $review->submitted_at,
            ]),
        'discipline_details' => DutyTrip::where('employee_id', $this->id)
            ->whereBetween('starts_at', [$period->starts_at->startOfDay(), $period->ends_at->endOfDay()])
            ->with('attendance')
            ->get()
            ->map(fn ($trip) => [
                'destination' => $trip->destination,
                'starts_at' => $trip->starts_at,
                'attendance_status' => $trip->attendance?->status?->label() ?? 'Tidak hadir',
            ]),
    ];
}
```

### 9.4. Filament — Modal Breakdown View

Di modal "Rekomendasi Pelatihan", panggil `$employee->meritBreakdownForManager()`.
Render sebagai tabel read-only + section collapsible per komponen.

**Data untuk manager:**
- Total score + breakdown (angka besar, jelas)
- Detail tiap KPI (target vs capaian, ada riwayat perubahan)
- Detail review (siapa nilai berapa, tanggal)
- Detail disiplin (dinas mana aja, status absensi)

---

## 10. Ringkasan — Data yang Akan Ditampilkan

| Komponen | Ditampilkan | Tujuan |
|----------|------------|--------|
| TOTAL SCORE | Angka besar (0-100) | Acuan utama: apakah merit cukup |
| KPI breakdown | Per-indicator: target, capaian, bobot | Manager evaluasi: KPI mana yg kurang |
| Review breakdown | Per-review: siapa, nilai, tipe | Manager lihat: penilaian 360 fair? |
| Disiplin breakdown | Per-dinas: status absensi | Manager lihat: catatan disiplin |
| Est. Bonus | Rupiah | Pertimbangan finansial |
| Riwayat KPI | Log perubahan | Pastikan data valid / tidak dimanipulasi |

---

## 11. Referensi

- BRD: `docs/brd.md` — FR-KAR-05 s.d FR-KAR-08 (Pelatihan), FR-MRT-01 s.d FR-MRT-08 (Merit)
- Panel Manager: `docs/panel-atasan.md` — §4.3 (Hasil Merit), §5.4 (Pengajuan Pelatihan)
- Panel Employee: `docs/panel-pegawai.md` — §4 (Kinerja), §5 (Pengembangan)
- Merit formula: `app/Services/MeritCalculator.php`
- Formula test: `tests/Feature/MeritSystemTest.php` — test `test_merit_formula_and_two_stage_publication`
- Audit trail: `app/Models/ActivityLog.php`
