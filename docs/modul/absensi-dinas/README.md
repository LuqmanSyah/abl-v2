# Absensi Dinas

Layanan absensi berbasis lokasi (GPS) + verifikasi wajah untuk pegawai yg sedang dinas luar.

## Alur

```
Perintah Dinas (Approved)
  ↓
Buka halaman absensi → preload face-api models
  ↓
Aktifkan kamera → capture foto
  ↓
Parallel: ambil GPS + ekstraksi face descriptor
  ↓
Submit → AttendanceRecorder validasi (radius, waktu, wajah)
  ↓
Status: Valid | OutsideRadius | Late | NeedsReview
  ↓
HR verifikasi → Valid → dipakai hitung disiplin merit
```

## Komponen

| Layer | File | Fungsi |
|-------|------|--------|
| **Controller** | `app/Http/Controllers/AttendanceController.php` | Sajikan halaman, terima submit, serve foto |
| **Service** | `app/Services/AttendanceRecorder.php` | Logic bisnis: validasi, record, cross-check wajah |
| **Model** | `app/Models/Attendance.php` | Record absensi + face_descriptor |
| **Model** | `app/Models/DutyTrip.php` | Perintah dinas (trip) |
| **Enum** | `app/Enums/AttendanceStatus.php` | Valid, OutsideRadius, Late, NeedsReview |
| **Enum** | `app/Enums/DutyTripStatus.php` | Draft, Approved, Completed, Cancelled |
| **View** | `resources/views/attendance/capture.blade.php` | Halaman PWA — kamera, GPS, watermark, queue offline |
| **JS** | `public/js/face-verification.js` | Preload model, ekstraksi 128-dim descriptor |
| **JS** | `public/js/face-api.js` | TensorFlow.js face recognition (~1.3 MB) |
| **SW** | `public/sw.js` | Cache model + queue sync offline |
| **Server Face** | `app/Http/Controllers/FaceVerificationController.php` | Fallback ekstraksi descriptor via Python |
| **Python** | `resources/python/face_extract.py` | Server-side face extraction (opsional) |

## Aturan Bisnis

| Aturan | Implementasi |
|--------|-------------|
| Absen hanya dlm radius dinas | `GeoDistance::meters()` vs `trip->radius_meters` |
| 1 absensi per hari per trip | Deteksi duplikat `whereDate('attendance_date', $today)` |
| Wajib foto | `photo` required, max 5MB |
| Verifikasi wajah | 128-dim descriptor, euclidean distance threshold 0.6 |
| Clock tolerance | `hr.attendance_clock_tolerance_minutes` (default 15 menit) |
| GPS akurasi > 100m = NeedsReview | `accuracy_meters > 100` |
| Mock location = NeedsReview | Flag dari JS `mock_location_suspected` |
| Offline queue | IndexedDB + Service Worker sync |
| HR verifikasi manual | Filament action `verifyByHr()` |

## Flowchart Detail

Lihat `verifikasi-wajah.md` untuk arsitektur verifikasi wajah lengkap + diagram.

## Test

| File | Jumlah |
|------|--------|
| `tests/Feature/DutyAttendanceTest.php` | 22 test |
| `tests/Feature/FlowTest.php` | 1 test (end-to-end) |

## Fix & Optimasi

| Item | Status | Catatan |
|------|--------|---------|
| Multi-hari per trip | ✅ | Attendance record per `captured_at` date |
| Preload face-api | ✅ | Init model saat page load |
| Early extraction | ✅ | Descriptor diambil parallel GPS |
| SW cache models | ✅ | Pre-cache di install event |
| Server-side fallback | ✅ | `/api/face/extract` via Python |
