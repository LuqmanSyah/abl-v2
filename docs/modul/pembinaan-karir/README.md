# Pembinaan Karir

Layanan pengembangan pegawai — gap analysis kompetensi, pelatihan, mentoring, dan perencanaan karier.

## Sub-layanan

### 1. Gap Analysis Kompetensi

Menganalisis kesenjangan kompetensi pegawai terhadap jabatan tujuan.

```
CareerGoal (jabatan tujuan)
  ↓
Ambil EmployeeCompetency level saat ini
  ↓
Bandingkan dgn PositionCompetency (standar jabatan tujuan)
  ↓
Gap = max(0, required - current)
  ↓
Rekomendasi: Training aktif / Ajukan mentoring
```

| File | Fungsi |
|------|--------|
| `app/Services/CareerGapService.php` | `analyze()` & `summary()` — hitung gap + rekomendasi |
| `app/Models/CareerGoal.php` | Target karier pegawai (jabatan tujuan) |
| `app/Models/EmployeeCompetency.php` | Level kompetensi pegawai (1-5) |
| `app/Models/PositionCompetency.php` | Standar kompetensi per jabatan |
| `app/Models/Competency.php` | Master data kompetensi |

### 2. Pelatihan (Training)

Pengajuan & rekomendasi pelatihan dengan workflow multi-tahap.

#### Alur Request (Pegawai Mengajukan)
```
Employee ajukan → Manager approve → HR verify → Complete
                  ↓                    ↓
              Bisa ditolak          Bisa ditolak
                  ↓
              Resubmit (Employee)
```

#### Alur Rekomendasi (Manager Merekomendasikan)
```
Manager rekomendasi (berdasarkan hasil merit) → langsung Approved
  ↓
HR verify → Complete
```

| File | Fungsi |
|------|--------|
| `app/Models/Training.php` | Master pelatihan (nama, tipe, provider) |
| `app/Models/TrainingRequest.php` | Pengajuan/rekomendasi pelatihan + workflow |
| `app/Enums/TrainingRequestStatus.php` | PendingManager, PendingHr, Approved, Rejected, Completed |
| `app/Notifications/TrainingPending.php` | Notifikasi ke manager |
| `app/Notifications/TrainingVerified.php` | Notifikasi ke employee |

### 3. Mentoring

Pendampingan atasan ke bawahan.

```
Employee ajukan mentoring → Manager setujui + jadwalkan → Complete
                            ↓
                         Bisa ditolak
```

| File | Fungsi |
|------|--------|
| `app/Models/Mentoring.php` | Mentoring request + workflow |
| `app/Enums/MentoringStatus.php` | Pending, Approved, Rejected, Completed |

## Integrasi

```
MeritSystem ──→ TrainingRequest::recommendByManager()
                    (rekomendasi berdasarkan hasil merit)
                        ↓
Pembinaan Karir ←── MeritSystem
  (gap analysis via CareerGapService)
```

## Aturan Bisnis

| Aturan | Implementasi |
|--------|-------------|
| Jabatan tujuan harus lebih tinggi | `CareerGoal::booted` — `level > current` |
| Gap analysis otomatis | `CareerGoal->gap_summary` attribute |
| Level kompetensi 1-5 | `EmployeeCompetency::booted`, `PositionCompetency::booted` |
| Pelatihan hanya untuk bawahan | `TrainingRequest::creating` validasi manager-employee |
| 1 request per training per pegawai | `recommendByManager` cek duplikat |
| Mentoring tidak bisa lampau | `Mentoring::creating` — `requested_at` |
| Delegasi atasan | `actorIsManager()` — `delegate_id` |
| Kompetensi hanya untuk Employee | `EmployeeCompetency::booted` |

## Test

| File | Jumlah |
|------|--------|
| `tests/Feature/CareerDevelopmentTest.php` | 9 test |
| `tests/Feature/TrainingWorkflowTest.php` | 7 test |
| `tests/Feature/MentoringWorkflowTest.php` | 7 test |
| `tests/Feature/FlowTest.php` | 1 test (end-to-end) |
