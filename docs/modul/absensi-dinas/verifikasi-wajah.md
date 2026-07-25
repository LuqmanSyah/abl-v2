# Verifikasi Wajah Absensi Dinas

## Arsitektur

```mermaid
flowchart TD
    A[Page Load] --> B[Preload: face-api.js models\n/ tinyFaceDetector\n/ faceLandmark68Net\n/ faceRecognitionNet]
    C[User Capture Photo] --> D[Extract face descriptor\n PARALLEL with GPS]
    D --> E{Face detected?}
    E -->|No| F[descriptor = null\nstatus = NeedsReview]
    E -->|Yes| G[128-dim float array]
    G --> H[Compare with previous\ndescriptor (euclidean distance)]
    H -->|distance < 0.6| I[Matching face]
    H -->|distance >= 0.6| J[Non-matching face\nstatus = NeedsReview]
    I --> K[Submit attendance\n+ descriptor]
    J --> K
    F --> K
    K --> L[Backend: AttendanceRecorder\nvalidates & stores descriptor]
    L --> M[ActivityLog: attendance.created]
    M --> N[HR notified if NeedsReview]
    N --> O[HR verifies via Filament]
    O --> P[status = Valid]
    P --> Q[Used in MeritCalculator\ndiscipline score]
```

## Alur Data

| Langkah | Lokasi | Detail |
|---------|--------|--------|
| 1. Preload model | Browser (page load) | `face-verification.js:init()` — download 3 model JSON + weight files dari `/models/`. Cache di Service Worker |
| 2. Capture foto | Browser (user action) | Video frame → JPEG blob (resize max 1600px, quality 85%) |
| 3. Extract descriptor | Browser (segera setelah capture) | `face-verification.js:extractFromBlob()` — `detectSingleFace(inputSize:320)` → `withFaceLandmarks()` → `withFaceDescriptor()` |
| 4. Validasi client | Browser (sebelum submit) | Bandingkan dgn `previousDescriptor` dari server (euclidean distance, threshold 0.6) |
| 5. Kirim ke server | Browser → Server | `face_descriptor` (JSON 128 float) dikirim dlm FormData bersama foto & lokasi |
| 6. Validasi server | `AttendanceRecorder.php:148-175` | Validasi array = 128 finite float |
| 7. Simpan | DB `attendances.face_descriptor` | TEXT column, nullable |
| 8. Cross-check | `AttendanceRecorder.php:101-131` | Bandingkan dgn descriptor sesi sebelumnya (employee + duty_trip sama). Jika mismatch >0.6 → status `NeedsReview` |
| 9. Notifikasi | `AttendanceRecorder.php:135-142` | Notifikasi ke semua HR aktif via `AttendanceNeedsReview` |
| 10. Verifikasi HR | `Filament:ViewAttendance.php` | HR klik "Verifikasi Absensi" → `verifyByHr()` → status jadi `Valid` |
| 11. Hitung disiplin | `MeritCalculator` | Attendance status Valid dipakai untuk discipline score |

## Komponen

### Frontend

| File | Fungsi |
|------|--------|
| `public/js/face-api.js` | Library face-api.js (~1.3 MB) — TensorFlow.js + model definitions |
| `public/js/face-verification.js` | Wrapper — init models, extract descriptor, verify against previous |
| `resources/views/attendance/capture.blade.php` | Halaman absensi — kamera, GPS, submit |
| `public/sw.js` | Service Worker — cache model files untuk akses cepat |
| `public/models/*` | Model weights: tinyFaceDetector, faceLandmark68Net, faceRecognitionNet |

### Backend

| File | Fungsi |
|------|--------|
| `app/Http/Controllers/AttendanceController.php` | Menyajikan halaman absensi + menerima submit |
| `app/Services/AttendanceRecorder.php` | Logic bisnis: validasi descriptor, cross-check, simpan ke DB |
| `app/Models/Attendance.php` | Model Attendance dgn field `face_descriptor` |
| `app/Http/Controllers/FaceVerificationController.php` | Server-side fallback endpoint (via Python) |
| `resources/python/face_extract.py` | Python script untuk ekstraksi descriptor server-side |

## Optimasi Performa (2026-07-25)

### Masalah
Verifikasi wajah lambat karena:
1. Model face-api.js (3 file) di-download setiap kali user submit
2. Deteksi wajah berjalan SEQUENTIAL dengan GPS, tidak parallel

### Solusi

| # | Optimasi | File | Dampak |
|---|----------|------|--------|
| 1 | **Preload model saat page load** | `capture.blade.php` panggil `FaceVerification.init()` segera setelah render. Model siap sebelum user capture foto | Eliminasi waktu download model dari jalur kritis submit |
| 2 | **Ekstraksi descriptor segera setelah capture** | `capture.blade.php:capturePhoto()` → `extractFaceEarly()` — ekstraksi berjalan parallel dengan pembacaan GPS | Deteksi wajah selesai sebelum user klik submit |
| 3 | **Service Worker cache** | `sw.js` pre-cache model files di event `install`. Serve dari cache untuk kunjungan berikutnya | Kunjungan kedua+ tidak perlu download model |
| 4 | **Server-side fallback** | `FaceVerificationController` — endpoint `/api/face/extract` via Python `face_recognition` | Opsi saat client tidak support face-api |

### Detail Implementasi

#### Preload Model (`capture.blade.php`)
```javascript
// Sebelum (lazy — model dimuat saat submit):
// submit handler → FaceVerification.verify(blob, prev) → init() → download models → detect

// Sesudah (preload — model siap sebelum user capture):
FaceVerification.init().catch(() => {}); // page load
// submit handler → descriptor sudah siap di cachedFaceDescriptor
```

#### Early Extraction (`capture.blade.php`)
```javascript
// capturePhoto() → toBlob() → extractFaceEarly(blob)
// extractFaceEarly: FaceVerification.extractFromBlob(blob) → cachedFaceDescriptor
// submit handler: pakai cachedFaceDescriptor langsung, tanpa loading
```

#### Service Worker (`sw.js`)
```javascript
// install event: cache.addAll(MODEL_PATHS) — pre-cache saat SW aktif
// fetch: /models/* → caches.match first, fetch fallback
// CACHE = 'sdm-portal-v2' (shared with panel pages)
```

#### Server-side Endpoint
```
POST /api/face/extract
  Body: photo (file, max 5MB)
  Response: { descriptor: [128 float] | null, error?: string }
  Auth: required (auth + active middleware)
  Throttle: 20/1 menit
```

### Dependencies Server-side
Untuk mengaktifkan server-side face verification:
```bash
pip install face_recognition
# Membutuhkan: cmake, dlib, Python dev headers
```

## Testing

### Unit Test
```
tests/Feature/DutyAttendanceTest.php:
  test_face_descriptor_must_contain_128_finite_numbers()
  test_face_verification_mismatch_sets_needs_review()
```

### Skenario Manual
1. Buka halaman absensi dinas → cek console: model ter-load
2. Ambil foto → status "Encoding wajah disimpan sebagai referensi."
3. Ambil foto kedua → status "Wajah cocok dengan absensi sebelumnya."
4. Ambil foto dengan wajah berbeda → status "Wajah tidak cocok (jarak: X.XX)"
5. Ambil foto tanpa wajah → status "Wajah tidak terdeteksi..."
6. Matikan internet → ambil foto → submit → "Tersimpan luring"
7. Hidupkan internet → absensi tersinkron otomatis

## Database

```sql
-- Migration: add_face_descriptor_to_attendances (2026-07-19)
ALTER TABLE attendances ADD COLUMN face_descriptor TEXT NULL AFTER photo_path;

-- Query cross-check
SELECT a1.employee_id, a1.id, a1.captured_at, a2.id, a2.captured_at
FROM attendances a1
JOIN attendances a2 ON a1.employee_id = a2.employee_id
  AND a1.duty_trip_id = a2.duty_trip_id
  AND a1.id < a2.id
  AND a1.face_descriptor IS NOT NULL
  AND a2.face_descriptor IS NOT NULL;
```
