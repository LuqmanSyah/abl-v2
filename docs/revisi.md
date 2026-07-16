# Revisi — Alur Rekomendasi Pelatihan oleh Atasan

---

## 1. Latar Belakang

Saat ini, pelatihan hanya dapat diajukan oleh Pegawai melalui menu **Pengajuan Pelatihan**.
Atasan hanya berperan menyetujui atau menolak. Atasan belum memiliki akses melihat rincian merit
bawahannya saat merekomendasikan pelatihan berdasarkan pencapaian merit.

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
        → HR pantau tanpa antrean verifikasi
          → HR complete (setelah selesai)
```

**Poin penting:**
- Threshold merit tidak di-enforce oleh sistem. Atasan lihat score merit pegawai lalu decide manual.
- Manager create langsung `Approved` — tidak perlu manager approve step (karena dia yang buat).
- Tidak perlu verifikasi HR. `hr_verified_by` dan `hr_verified_at` tetap `null`; HR hanya memantau dan complete setelah training selesai.
- Counter HR hanya menghitung `PendingHr`, sehingga rekomendasi Atasan tidak menambah antrean verifikasi.
- Employee lihat training langsung di daftar "Pengajuan Pelatihan" miliknya.
- Alur lama tetap berlaku untuk pengajuan yang dibuat Pegawai.
- Revisi ini menggantikan ketentuan BRD lama yang mewajibkan verifikasi HR untuk semua jalur pelatihan.

---

## 3. Ringkasan Perubahan

### 3.1. Tidak Berubah

| Aspek | Status |
|-------|--------|
| Model kolom TrainingRequest | ✅ Pakai existing fields (`manager_id`, `status`, `manager_decided_at`) |
| Tabel database | ✅ Tidak ada migrasi baru |
| Employee panel | ✅ Tidak ada perubahan UI |
| HR panel | ✅ Aksi existing `complete()` dipakai; rekomendasi tidak menampilkan aksi verifikasi HR |
| TrainingRequestStatus enum | ✅ `Approved` sudah ada |
| ActivityLog schema | ✅ Pakai kolom `action` dan JSON `data` existing; tidak perlu migrasi |
| Flow employee request | ✅ Tidak berubah — employee tetap bisa request sendiri |
| Existing tests | ✅ Alur Pegawai tetap; tambah test flow rekomendasi dan audit |

### 3.2. Berubah

| # | File | Perubahan |
|---|------|-----------|
| 1 | `app/Models/TrainingRequest.php` | Tambah jalur domain `recommendByManager()`, direct `Approved`, guard status, audit snapshot tunggal. |
| 2 | `app/Models/MeritResult.php` | Tambah breakdown terotorisasi berdasarkan periode dari row merit. |
| 3 | `app/Filament/Resources/MeritResults/Tables/MeritResultsTable.php` | Tambah row action dan modal rekomendasi. |
| 4 | `resources/views/filament/resources/merit-results/recommend-training-breakdown.blade.php` | Render breakdown merit, KPI, review, disiplin, dan riwayat KPI. |
| 5 | `app/Models/EmployeeKpi.php` | Catat `kpi.created`, `kpi.updated`, dan `kpi.deleted`. |
| 6 | `app/Models/PerformanceReview.php` | Enforce review terkirim tidak dapat diubah/dihapus. |
| 7 | `tests/Feature/CareerDevelopmentTest.php`, `tests/Feature/MeritSystemTest.php` | Test rekomendasi, otorisasi, audit, dan regresi. |

`ManagerPanelProvider` tidak berubah karena `MeritResultResource` dan `TrainingRequestResource` sudah terdaftar.

---

## 4. Detail Implementasi

### 4.1. Model — `app/Models/TrainingRequest.php`

Semua rekomendasi wajib melewati satu jalur domain:

```php
public static function recommendByManager(
    User $manager,
    User $employee,
    Training $training,
    MeritResult $meritResult,
    string $reason,
): self;
```

Contract method:

- Manager dan employee wajib aktif; employee wajib bawahan langsung manager.
- Training wajib aktif dan belum pernah diajukan/direkomendasikan untuk employee tersebut.
- `MeritResult` wajib milik employee yang dipilih; periode tidak ditebak dari record terbaru.
- Reason wajib terisi.
- Pembuatan request dan activity log berjalan dalam satu transaksi.
- Status awal `Approved`, `manager_decided_at` terisi, sedangkan field verifikasi HR tetap `null`.
- Satu log `training.recommended` menyimpan snapshot semua komponen merit. Event `training.requested` tidak dibuat untuk jalur ini.
- Direct create dengan status `Approved` di luar method ditolak oleh model.

### 4.2. Manager Panel — Custom Action

Tambahkan tombol **Rekomendasikan Pelatihan** pada setiap row `MeritResultsTable` milik bawahan langsung. Row menjadi sumber employee, periode, dan snapshot merit; tidak ada halaman create umum.

**Modal form:**

- Employee dan periode dari row context.
- Total score, bobot, dan semua komponen merit.
- Detail KPI beserta `kpi.created`/`kpi.updated`.
- Detail review dan dinas yang benar-benar masuk formula disiplin.
- Training aktif yang belum pernah diajukan employee.
- Reason wajib.

Submit memanggil `TrainingRequest::recommendByManager($manager, $employee, $training, $meritResult, $reason)`.

### 4.3. Employee Panel — Tanpa Perubahan

Employee langsung melihat rekomendasi berstatus `Approved` pada daftar "Pengajuan Pelatihan". Scope `visibleTo()` existing sudah menangani akses.

### 4.4. HR Panel — Tidak Ada Antrean Approval Baru

HR melihat rekomendasi pada daftar global, tetapi tidak mendapat aksi **Verifikasi HR** karena status sudah `Approved`. Aksi existing `complete()` tetap dipakai untuk mencatat hasil. Counter `PendingHr` tidak bertambah.

---

## 5. Files yang Kena Dampak

```
app/Models/TrainingRequest.php
app/Models/MeritResult.php
app/Models/EmployeeKpi.php
app/Models/PerformanceReview.php
app/Filament/Resources/MeritResults/Tables/MeritResultsTable.php
resources/views/filament/resources/merit-results/recommend-training-breakdown.blade.php
tests/Feature/CareerDevelopmentTest.php
tests/Feature/MeritSystemTest.php
docs/brd.md
docs/panel-atasan.md
docs/panel-pegawai.md
docs/panel-hr.md
```

---

## 6. Test Plan

| # | Skenario | Langkah | Ekspektasi |
|---|----------|---------|------------|
| 1 | Manager rekomendasi training — valid | Manager pilih row merit bawahan dan training aktif | Status `Approved`, `manager_decided_at` terisi, field verifikasi HR `null` |
| 2 | Manager rekomendasi — bukan bawahan | Manager create untuk employee bukan bawahannya | Error |
| 3 | Manager rekomendasi — training nonaktif | Pilih training dengan `is_active=false` | Error |
| 4 | Employee lihat rekomendasi | Employee login, buka daftar training request | Training rekomendasi muncul |
| 5 | HR complete training rekomendasi | HR complete training yg direkomendasi | Status `Completed` |
| 6 | Employee request sendiri — masih jalan | Employee create request sendiri | Status `PendingManager` (tidak berubah) |
| 7 | Antrean HR | Manager membuat rekomendasi | Tidak ada request `PendingHr` baru |
| 8 | Audit | Manager membuat rekomendasi | Tepat satu `training.recommended`, tanpa `training.requested`, snapshot merit tersimpan |
| 9 | Duplikat | Training sama pernah diajukan employee | Domain error yang jelas |
| 10 | Audit KPI | KPI dibuat lalu capaian diubah | Log old/new tampil pada modal |
| 11 | Otorisasi modal | Atasan lain membuka breakdown | Error |

---

## 7. Urutan Implementasi

```
1. Model + invariant rekomendasi
2. Test flow dan antrean HR
3. Breakdown + audit KPI
4. Row action dan modal Filament
5. Sinkronisasi BRD dan dokumentasi panel
6. Focused test, full test, lalu simulasi tiga role
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

#### 8.1.5. Total Score (tidak di-clamp)

```
total_score = (
    kpi_score × kpi_weight
  + discipline_score × discipline_weight
  + manager_score × manager_weight
  + review_360_score × review_360_weight
) / 100
```

**Sumber data:** Bobot dari `review_periods`, nilai dari perhitungan di atas

KPI dapat mencapai 120 dan implementasi tidak melakukan clamp total. Dengan bobot default 40/20/20/20, rentang total adalah 0-108. Formula maksimum umum: `100 + (0.2 × kpi_weight)`. Karena itu estimated bonus juga dapat melebihi base bonus. Jika kebijakan menghendaki batas 100, perubahan formula harus menjadi revisi terpisah.

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
                                                            ──→ TOTAL SCORE (tidak di-clamp)
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

Riwayat KPI mulai tersedia setelah logging `kpi.*` diterapkan. `updated_at` lama tidak menyimpan old/new value, sehingga perubahan sebelum revisi ini tidak dapat direkonstruksi.

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
| KPI target/achievement | `activity_logs` action `kpi.created`, `kpi.updated`, `kpi.deleted` dengan payload old/new | `employee_kpis`, `activity_logs` |
| Manager review | `performance_reviews` (immutable setelah submit) | `performance_reviews` |
| 360 review | `performance_reviews` (immutable setelah submit) | `performance_reviews` |
| Attendance status | `attendances.status` | `attendances` |
| Merit publish | `activity_logs` action `merit.*` | `activity_logs` |
| Keputusan rekomendasi | `training.recommended` dengan snapshot komponen merit | `training_requests`, `activity_logs` |

### 9.3. Implementasi — Method di Model

**`app/Models/MeritResult.php` — method baru:**

```php
public function breakdownForManager(User $manager): array;
```

Method memakai periode milik row `MeritResult`, memverifikasi bahwa employee adalah bawahan langsung manager, membaca log KPI, dan memakai filter dinas identik dengan `MeritCalculator`: `Completed` atau `Approved` yang sudah berakhir. Ini mencegah modal menampilkan data yang tidak ikut membentuk score.

### 9.4. Filament — Modal Breakdown View

Di modal "Rekomendasi Pelatihan", panggil `$meritResult->breakdownForManager($manager)`.
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
| TOTAL SCORE | Angka besar; maksimum 108 pada bobot default | Acuan utama: apakah merit cukup |
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

---

## 12. Revisi UI, Otorisasi Aksi, dan Verifikasi Absensi

Revisi ini diterapkan pada seluruh panel Filament: Pegawai, Atasan, dan HR.

### 12.1. Tombol Berdasarkan Aktor

- Tombol create, edit, delete, bulk delete, dan action alur kerja mengikuti role, kepemilikan record, serta status data.
- Action bawaan Filament memakai aturan visibilitas eksplisit dari `Resource::canCreate()`, `canEdit()`, dan `canDelete()` agar tombol tidak muncul sebelum route menolak akses.
- Action Atasan pada mentoring, pengajuan pelatihan, merit, dan dinas hanya tampil untuk data bawahannya sendiri.
- Bulk delete KPI hanya tersedia untuk Atasan dan tetap memeriksa otorisasi setiap record.
- Bulk delete indikator KPI juga memeriksa otorisasi tiap record agar indikator pada periode merit terpublikasi tidak ikut terhapus.
- Pegawai dan HR tidak melihat tombol pengelolaan KPI.
- Tombol create dihapus dari Absensi dan Hasil Merit karena kedua resource bersifat read-only.
- Action yang tidak sesuai status disembunyikan, bukan hanya dibuat nonaktif.
- Validasi model tetap menjadi lapisan pengaman saat action dipanggil langsung.

### 12.2. Label Sidebar Formal

| Resource | Pegawai | Atasan | HR |
|----------|---------|--------|----|
| Dinas | Pelaksanaan Dinas | Pengelolaan Dinas | Monitoring Dinas |
| Absensi | Riwayat Absensi | Monitoring Absensi | Monitoring Absensi |
| KPI | Capaian KPI | Pengelolaan KPI | Monitoring KPI |
| Merit | Hasil Merit | Verifikasi Merit | Publikasi Merit |
| Kompetensi | Profil Kompetensi | Monitoring Kompetensi | Pengelolaan Kompetensi Pegawai |
| Karier | Rencana Karier | Monitoring Karier | Monitoring Karier |
| Pelatihan | Katalog Pelatihan | Katalog Pelatihan | Pengelolaan Pelatihan |
| Pengajuan Pelatihan | Pengajuan Pelatihan | Persetujuan Pelatihan | Verifikasi Pelatihan |
| Mentoring | Pengajuan Mentoring | Pengelolaan Mentoring | Monitoring Mentoring |
| Performance review | Umpan Balik Kinerja | Umpan Balik Kinerja | Umpan Balik Kinerja |

Istilah UI **Penilaian 360** diganti menjadi **Umpan Balik Kinerja**. Nama kolom database `review_360_score` dan `review_360_weight` tetap dipertahankan agar tidak memerlukan migrasi.

### 12.3. Standar Modal Filament

- Heading rata kiri, footer action rata kanan, header/footer sticky.
- Modal create dan edit memakai lebar `2xl`.
- Modal workflow memakai lebar `lg`.
- Modal konfirmasi memakai lebar `md`.
- Modal breakdown rekomendasi pelatihan memakai lebar `5xl`.
- Heading, deskripsi dampak, dan label submit dibuat spesifik untuk tiap action.
- Tampilan modal memakai border, radius, shadow, separator, dark mode, dan layout mobile yang konsisten.

### 12.4. Verifikasi Absensi Dinas

Absensi berstatus `NeedsReview` sebelumnya tidak memiliki transisi penyelesaian. HR sekarang mendapat action **Verifikasi** pada daftar/detail Dinas serta daftar/detail Absensi.

Alur:

1. Absensi terdeteksi memiliki GPS, akurasi, atau selisih waktu mencurigakan.
2. Status menjadi `NeedsReview` atau **Memerlukan Pemeriksaan**.
3. HR memeriksa detail lokasi dan foto.
4. HR menekan **Verifikasi Absensi**.
5. Status berubah menjadi `Valid`.
6. Sistem mencatat activity log `attendance.verified`.

Method model:

```php
public function verifyByHr(User $hr): void;
```

Method memakai transaction dan row lock. Role selain HR atau absensi dengan status selain `NeedsReview` ditolak menggunakan `DomainException`.

### 12.5. Verifikasi

- Test transisi `NeedsReview` menjadi `Valid`.
- Test penolakan verifikasi oleh Atasan.
- Test pencatatan `attendance.verified`.
- Test tombol verifikasi terlihat untuk HR dan tersembunyi untuk Atasan.
- Test tombol verifikasi tersedia langsung pada modul Dinas.
- Test seluruh label sidebar berdasarkan role.
- Test bulk delete KPI hanya tersedia untuk Atasan.
- Test matriks visibilitas tombol create pada delapan resource lintas-role.
- Test Absensi dan Hasil Merit tidak memiliki action create.
- Test action edit KPI hanya terlihat untuk Atasan pemilik data.
- Test render lima halaman detail dengan section baru.

### 12.6. Tampilan Detail Terstruktur

Halaman detail tidak lagi menampilkan seluruh field dalam satu daftar panjang. Informasi dibagi menjadi section responsif:

- **Dinas:** informasi penugasan, lokasi absensi, lampiran, dan riwayat.
- **Absensi:** ringkasan absensi, data GPS dan foto, serta sinkronisasi.
- **KPI Pegawai:** informasi indikator, target, capaian, dan catatan.
- **Umpan Balik Kinerja:** pihak yang terlibat dan hasil penilaian.
- **Hasil Merit:** ringkasan merit, komponen nilai, status verifikasi, dan riwayat.

Data teknis dan riwayat dibuat collapsible agar detail utama tetap mudah dipindai. Nama verifikator merit ditampilkan menggantikan ID pengguna.
