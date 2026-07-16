# Testing List — Sistem Manajemen SDM (ABL)

## Daftar Pengujian Sebelum Deployment

---

### A. BUG DITEMUKAN

#### 1. `canAttend()` vs `record()` — Status Conflict
- **Lokasi**: `app/Http/Controllers/AttendanceController.php:93-99` dan `app/Services/AttendanceRecorder.php:40`
- **Tingkat**: **Critical**
- **Deskripsi**: `canAttend()` mengizinkan trip status `Completed`, tapi `record()` hanya menerima `Approved`. Jika trip sudah `Completed` (dari sesi sebelumnya) tapi user membuka halaman absen lagi, form tampil tapi submit selalu gagal.
- **Fix**: Sinkronkan pengecekan — `canAttend()` hanya izinkan `Approved`, atau `record()` terima juga `Completed`.

#### 2. Mentoring State Transitions — Race Condition
- **Lokasi**: `app/Models/Mentoring.php:65-97`
- **Tingkat**: **Critical**
- **Deskripsi**: Method `approve()`, `reject()`, `complete()` tidak menggunakan DB transaction + `lockForUpdate()`. Dua request simultan bisa approve mentoring yang sama. `TrainingRequest` punya mekanisme locking (`transition()`), Mentoring tidak.
- **Fix**: Implementasi pattern `transition()` dengan `DB::transaction()` dan `lockForUpdate()` seperti TrainingRequest.

#### 3. Score Kolom — Range Tidak Tervalidasi
- **Lokasi**: `database/migrations/2026_07_15_020000_create_merit_tables.php:56` dan `app/Services/MeritCalculator.php:81`
- **Tingkat**: **High**
- **Deskripsi**: Kolom `score` di `performance_reviews` adalah `unsignedTinyInteger` (0-255). Tapi kode di `reviewScore()` membagi dengan 5 (mengasumsikan skala 0-5). Pengguna bisa input score 100, salah dihitung jadi `100/5*100 = 2000%`.
- **Fix**: Tambah validation rule `max:5` di model atau request.

#### 4. Attendance Selalu Completed — Bahkan untuk Flagged Data
- **Lokasi**: `app/Services/AttendanceRecorder.php:80`
- **Tingkat**: **High**
- **Deskripsi**: Baris `$trip->update(['status' => DutyTripStatus::Completed])` dijalankan untuk semua status absensi — termasuk `OutsideRadius`, `Late`, `NeedsReview`. Trip langsung selesai meski absensi bermasalah, pegawai tidak bisa ambil ulang.
- **Fix**: Hanya set Completed jika status `Valid`, atau buat mekanisme retake untuk absensi flagged.

#### 5. `reviewScore()` — PHP Falsy Bug
- **Lokasi**: `app/Services/MeritCalculator.php:81`
- **Tingkat**: **Medium**
- **Deskripsi**: `$average ? (float) $average / 5 * 100 : 0` — Jika `avg('score')` menghasilkan `0.0` (semua score 0), PHP anggap falsy, return 0. Fungsi sama dengan tidak ada review. Skor 0 seharusnya `0/5*100 = 0`, hasilnya sama angka, tapi logika salah secara konsep.
- **Fix**: Gunakan `$average !== null` bukan `$average`.

#### 6. Tidak Ada Validasi `captured_at` Masa Depan
- **Lokasi**: `app/Services/AttendanceRecorder.php:44`
- **Tingkat**: **Medium**
- **Deskripsi**: `$capturedAt = CarbonImmutable::parse($data['captured_at'])` — Tidak ada pengecekan apakah `captured_at` di masa depan. Device dengan waktu salah bisa kirim timestamp masa depan, lolos sebagai absensi valid.
- **Fix**: Tambah `$capturedAt->isFuture()` check → throw.

#### 7. Tidak Ada Rate Limiting Attendance Store
- **Lokasi**: `routes/web.php`
- **Tingkat**: **Medium**
- **Deskripsi**: Route `POST /pegawai/dinas/{dutyTrip}/absensi` tidak punya throttle. Brute force/flood attack bisa bypass rate limit.
- **Fix**: Tambah middleware `throttle:10,1` di route group.

#### 8. CSV Injection Protection Parsial
- **Lokasi**: `app/Http/Controllers/HrReportController.php:128-131`
- **Tingkat**: **Medium**
- **Deskripsi**: Hanya tangani `=`, `+`, `-`, `@` di awal string. Tab karakter, line break injection, dan formula encoding lain tidak ditangani.
- **Fix**: Gunakan pustaka CSV (League CSV) atau escape menyeluruh.

#### 9. Double Inactive Check — Redirect Loop Potensial
- **Lokasi**: `app/Http/Middleware/EnsureUserIsActive.php:12-27` dan `app/Http/Controllers/AuthenticatedSessionController.php:17-25`
- **Tingkat**: **Low**
- **Deskripsi**: Middleware logout + redirect ke login. Tapi `create()` di `AuthenticatedSessionController` juga cek inactive user → logout lagi + redirect. Double redirect, tidak krusial tapi tidak bersih.
- **Fix**: Biarkan middleware handle sepenuhnya, hapus duplicate check di controller.

#### 10. Mentor `guardManager()` Tidak Fresh Read
- **Lokasi**: `app/Models/Mentoring.php:108-112`
- **Tingkat**: **High**
- **Deskripsi**: Method `guardManager()` membaca `$this->manager_id` dan `$this->status` dari model dalam memori, bukan dari database. Dua request simultan bisa lolos guard karena keduanya baca status `Pending` dari memory.
- **Fix**: Tambah `$mentoring->fresh()` sebelum guard check, atau implementasi `transition()` seperti TrainingRequest.

#### 11. MeritCalculator — Recalculation Hapus Verifikasi Manager (MC-3)
- **Lokasi**: `app/Services/MeritCalculator.php:59-60`
- **Tingkat**: **Critical**
- **Deskripsi**: Setiap `calculate()` set `manager_verified_by=null`, `hr_verified_by=null`, `published_at=null`. Cuma cek `published_at` untuk blokade. Jika HR recalculate setelah manager verify (tapi sebelum HR verify), **verifikasi manager terhapus**. Manager harus verify lagi.
- **Fix**: Tambah guard: jika `manager_verified_at` atau `hr_verified_at` terisi, throw error.

#### 12. MeritCalculator — `$kpi->indicator` Null Crash (MC-1)
- **Lokasi**: `app/Services/MeritCalculator.php:31-34`
- **Tingkat**: **High**
- **Deskripsi**: `EmployeeKpi::with('indicator')->get()`. Jika FK bypassed (indicator_id mengarah ke id terhapus), `$kpi->indicator` jadi null. Akses `$kpi->indicator->weight` di line 32 → **null pointer crash**.
- **Fix**: Skip employee_kpis dengan indicator null, atau tambah guard `$kpi->indicator?->weight ?? 0`.

#### 13. SqliteBackup — `SQLite3::backup()` PHP Version Requirement (SB-1)
- **Lokasi**: `app/Services/SqliteBackup.php:20`
- **Tingkat**: **High**
- **Deskripsi**: Method `SQLite3::backup()` baru ada di PHP 8.3.16+. Jika deployment pakai PHP < 8.3.16, **fatal error**. Tidak ada fallback. Saat ini composer require PHP ^8.2, jadi risiko tinggi.
- **Fix**: Deteksi `method_exists` atau minimum PHP version guard. Alternatif: implementasi backup manual pakai `VACUUM INTO`.

#### 14. AttendanceRecorder — `$receivedAt` Tidak Dicek vs `ends_at` (AR-1)
- **Lokasi**: `app/Services/AttendanceRecorder.php:46`
- **Tingkat**: **Low**
- **Deskripsi**: Cuma cek `$receivedAt->isBefore($trip->starts_at)`. Tidak ada cek `$receivedAt->isAfter($trip->ends_at)`. Employee bisa submit lama setelah dinas selesai. Clock mismatch flag cuma `NeedsReview`, bukan block.
- **Fix**: Tambah `$receivedAt->isAfter($trip->ends_at)` → throw "Sesi absensi sudah berakhir."

#### 15. AttendanceRecorder — Status Priority Nutup Data Lokasi (AR-2)
- **Lokasi**: `app/Services/AttendanceRecorder.php:62-67`
- **Tingkat**: **Medium**
- **Deskripsi**: `$suspected` (NeedsReview) diperiksa pertama. Jika clock mismatch + outside radius → cuma `NeedsReview`, HR tidak lihat bahwa lokasi juga salah. Informasi geografi hilang.
- **Fix**: Ubah status priority: cek `outside_radius` dan `late` dulu. Jika suspected, simpan data asli di `data` JSON column Attendance. Atau simpan multiple flags.

#### 16. MeritCalculator — Discipline = 100 Jika 0 Duty Trips (MC-2)
- **Lokasi**: `app/Services/MeritCalculator.php:49`
- **Tingkat**: **Medium**
- **Deskripsi**: `$dutyTripCount ? $validAttendanceCount / $dutyTripCount * 100 : 100`. Pegawai tanpa satupun perintah dinas dalam periode dapat nilai disiplin **sempurna 100**. Tidak adil dibanding pegawai yg sering ditugaskan.
- **Fix**: Jika 0 duty trips → discipline_score = 0, atau exclude discipline dari total (proporsional naikkan bobot lain).

#### 17. SqliteBackup — Tidak Ada Directory Check untuk Target (SB-2)
- **Lokasi**: `app/Services/SqliteBackup.php:17`
- **Tingkat**: **Medium**
- **Deskripsi**: `new SQLite3($targetPath)` — jika direktori target tidak ada, SQLite3 gagal create file.
- **Fix**: Tambah `$dir = dirname($targetPath); if (! is_dir($dir)) mkdir($dir, 0777, true);`

#### 18. CareerGapService — `target_position_id` Null Crash (CG-1)
- **Lokasi**: `app/Services/CareerGapService.php:17-19`
- **Tingkat**: **Low**
- **Deskripsi**: `PositionCompetency::where('position_id', $goal->target_position_id)`. Jika `CareerGoal` punya `target_position_id` null (DB constraint mencegah, tapi via kode bisa), `where('position_id', null)` return empty, `$standards` empty, tidak error tapi hasil meaningless. Jika `$goal->targetPosition` null → N+1 crash.
- **Fix**: Tambah null guard di method `analyze()`.

---

### B. TESTING LIST — Fitur & Skenario

#### 1. Autentikasi & Akses Panel

| # | Skenario | Prekondisi | Langkah | Ekspektasi |
|---|----------|-----------|---------|------------|
| 1 | Login sukses — Employee | User pegawai aktif | POST `/login` email=pegawai@example.com password=password | Redirect ke `/pegawai` |
| 2 | Login sukses — Manager | User atasan aktif | POST `/login` email=atasan@example.com | Redirect ke `/atasan` |
| 3 | Login sukses — HR | User HR aktif | POST `/login` email=hr@example.com | Redirect ke `/hr` |
| 4 | Login gagal — password salah | User valid | POST `/login` password=salah | Kembali ke login, error email |
| 5 | Login gagal — inactive user | User is_active=false | POST `/login` | Error "Akun dinonaktifkan" |
| 6 | Panel redirect — role mismatch | Employee login | Akses `/hr` | Redirect ke login / 403 |
| 7 | Inactive user — session logout | User inactive | Akses halaman terautentikasi | Redirect login, session cleared |
| 8 | Login page — panel login redirect | Any user | Akses `/pegawai/login` | Redirect ke `/login` |

#### 2. Organisasi (Master Data) — HR Only

| # | Skenario | Role | Langkah | Ekspektasi |
|---|----------|------|---------|------------|
| 1 | CRUD Unit | HR | Buat, edit, lihat unit | Sukses, validasi code unique |
| 2 | CRUD Position | HR | Buat position dengan unit_id, level | Sukses, unique per unit |
| 3 | Assign user ke unit & position | HR | Edit user → pilih unit & position | Position harus dari unit sama |
| 4 | Soft block — user punya bawahan | HR | Coba nonaktifkan manager punya subordinate | Error "Atasan yang masih memiliki bawahan" |
| 5 | Manager tidak bisa akses org resources | Manager | Akses `/hr/users` | Redirect/403 |
| 6 | Employee tidak bisa akses org resources | Employee | Akses `/hr/units` | Redirect/403 |

#### 3. Duty Trip (Perintah Dinas)

| # | Skenario | Role | Langkah | Ekspektasi |
|---|----------|------|---------|------------|
| 1 | Buat duty trip | Manager | Isi employee_id (bawahan), destination, dates | Sukses |
| 2 | Buat duty trip — bukan bawahan | Manager | Pilih employee bukan bawahan | Error "Pegawai harus bawahan langsung" |
| 3 | Approve trip | Manager | Trip default approved via seed | — |
| 4 | Cancel trip — by assigned manager | Manager | Cancel before starts_at + no attendance | Status Cancelled |
| 5 | Cancel trip — by wrong manager | Manager lain | Cancel trip manager lain | Error "tidak dapat dibatalkan" |
| 6 | Ubah trip — after attendance | Manager | Edit trip yang sudah ada absensi | Error "Lokasi dinas yang telah selesai tidak dapat diubah" |
| 7 | Lihat trip — employee scope | Employee | Buka daftar trip | Hanya trip milik sendiri |
| 8 | Lihat trip — manager scope | Manager | Buka daftar trip | Hanya trip untuk bawahannya |
| 9 | Lihat trip — HR scope | HR | Buka daftar trip | Semua trip |
| 10 | Ubah trip — before starts_at, no attendance | Manager | Edit trip yang masih future | Sukses |

#### 4. Attendance (Absensi)

| # | Skenario | Role | Langkah | Ekspektasi |
|---|----------|------|---------|------------|
| 1 | Lihat halaman capture | Employee | GET `/pegawai/dinas/{id}/absensi` | Halaman form muncul |
| 2 | Submit absensi valid | Employee | Di dalam radius, tepat waktu | Status Valid, trip marked Completed |
| 3 | Submit absensi — outside radius | Employee | Absen di luar radius >100m | Status OutsideRadius |
| 4 | Submit absensi — late | Employee | Absen setelah ends_at | Status Late |
| 5 | Submit absensi — mock location | Employee | GPS mock diaktifkan | Status NeedsReview |
| 6 | Submit absensi — clock mismatch | Employee | captured_at vs server time >15 menit | Status NeedsReview |
| 7 | Submit absensi — before starts_at | Employee | Waktu absen < starts_at | Error "Absensi belum dibuka" |
| 8 | Duplicate client_uuid — same duty | Employee | Kirim UUID sama | 409 Conflict / data existing |
| 9 | Idempotent re-submit | Employee | Kirim data persis sama | 200 OK, no duplicate record |
| 10 | Lihat foto attendance | Employee milik sendiri | GET `/absensi/{id}/foto` | OK |
| 11 | Lihat foto attendance — employee lain | Employee lain | GET `/absensi/{id}/foto` | 403 Forbidden |
| 12 | Lihat foto attendance — Manager | Manager bawahan | GET `/absensi/{id}/foto` | OK |
| 13 | **BUG: Completed trip show form** | Employee | Buka halaman capture trip Completed | Form muncul tapi submit gagal |
| 14 | Offline queue + sync | Employee | Submit offline, kemudian online | Queue tersimpan IndexedDB, terkirim saat online |
| 15 | Rate limit — attendance store | Employee | Kirim 50 request cepat | Belum ada throttle — potensi abuse |

#### 5. KPI & Merit System

| # | Skenario | Role | Langkah | Ekspektasi |
|---|----------|------|---------|------------|
| 1 | Buat review period | HR | Set bobot total 100% | Sukses |
| 2 | Buat review period — bobot != 100 | HR | Set bobot total 90% | Error "Total bobot merit wajib 100%" |
| 3 | Buat KPI indicator | HR | Set weight dalam 100% per periode | Sukses |
| 4 | Buat employee KPI | Manager | Pilih employee bawahan, set target | Sukses |
| 5 | KPI target <= 0 | Manager | Target = 0 | Error "Target KPI harus lebih dari 0" |
| 6 | KPI achievement negatif | Manager | Achievement = -1 | Error "Capaian KPI tidak boleh negatif" |
| 7 | Hitung merit | (system) | MeritCalculator::calculate() | Formula: weighted average |
| 8 | Verifikasi manager | Manager | verifyByManager() | manager_verified_at terisi |
| 9 | Verifikasi HR | HR | verifyByHr() | hr_verified_at + published_at terisi |
| 10 | Stale verification after recalc | HR | Hitung ulang, approve stale objek | Error "Verifikasi Atasan wajib selesai" |
| 11 | Merit visible — employee | Employee | Lihat merit results | Hanya yang published |
| 12 | Merit visible — manager | Manager | Lihat merit results | Bawahan sendiri |
| 13 | Edit KPI — after published | Manager | Edit KPI setelah merit published | Error "tidak dapat diubah" |
| 14 | Delete KPI — after published | Manager | Delete KPI setelah merit published | Error "tidak dapat dihapus" |
| 15 | **BUG: Score >5 pada PerformanceReview** | Employee/Manager | Set score = 100 | Tidak divalidasi, perhitungan kacau |
| 16 | Handle — zero achievement | Manager | achievement = 0, target > 0 | Perhitungan beres |

#### 6. Performance Review (Penilaian)

| # | Skenario | Role | Langkah | Ekspektasi |
|---|----------|------|---------|------------|
| 1 | Manager ke Employee | Manager | Buat review reviewee_id = bawahan | Sukses |
| 2 | Employee ke Manager | Employee | Buat review reviewer manager_id = reviewee | Sukses |
| 3 | Peer review — satu unit | Employee | Buat review ke kolega satu unit | Sukses |
| 4 | Peer review — berbeda unit | Employee | Buat review ke kolega beda unit | Error "Hubungan penilai dan pegawai tidak valid" |
| 5 | Self-review | Employee | Coba review diri sendiri (Peer) | Error |

#### 7. Competency & Career

| # | Skenario | Role | Langkah | Ekspektasi |
|---|----------|------|---------|------------|
| 1 | Buat competency | HR | Nama unique | Sukses |
| 2 | Set position competency standard | HR | Required level 1-5 | Sukses |
| 3 | Employee competency assessment | HR/Manager | Level 1-5, assessed_at | Sukses |
| 4 | Career goal — lebih tinggi | Employee | Target position level > current | Sukses |
| 5 | Career goal — tidak lebih tinggi | Employee | Target position <= current | Error |
| 6 | Gap analysis | Employee/Manager/HR | Lihat gap summary | Competency gap + rekomendasi tampil |

#### 8. Training & Mentoring

| # | Skenario | Role | Langkah | Ekspektasi |
|---|----------|------|---------|------------|
| 1 | Request training | Employee | Pilih training aktif, isi reason | PendingManager |
| 2 | Approve by manager | Manager | Approve request bawahannya | PendingHr |
| 3 | Approve by wrong manager | Manager lain | Approve request bukan bawahannya | Error |
| 4 | Reject by manager | Manager | Reject dengan notes | Rejected |
| 5 | Resubmit — after reject | Employee | Resubmit dengan alasan baru | PendingManager lagi |
| 6 | Resubmit — not rejected | Employee | Coba resubmit yang approved | Error |
| 7 | Verify by HR | HR | Verifikasi training PendingHr | Approved |
| 8 | Complete by HR | HR | Complete training Approved | Completed |
| 9 | Request mentoring | Employee | Set topic, target, requested_at (future) | Pending |
| 10 | Request mentoring — past date | Employee | requested_at di masa lalu | Error "Jadwal mentoring yang diajukan tidak boleh lampau" |
| 11 | Approve mentoring — past scheduled | Manager | Schedule time di masa lalu | Error "Jadwal mentoring yang disetujui tidak boleh lampau" |
| 12 | Complete mentoring | Manager | Set result + follow_up | Completed |
| 13 | **BUG: Mentoring race condition** | Manager | Approve + Reject simultan | Keduanya bisa lolos — tidak ada locking |
| 14 | Activity log check | All | Setiap transaksi state | ActivityLog tercatat |

#### 9. Laporan HR

| # | Skenario | Role | Langkah | Ekspektasi |
|---|----------|------|---------|------------|
| 1 | Akses halaman laporan | Employee/Manager | GET `/hr/laporan` | 403 Forbidden |
| 2 | Filter — unit | HR | Pilih unit tertentu | Hanya pegawai unit tersebut |
| 3 | Filter — position | HR | Pilih position tertentu | Hanya pegawai dengan position tsb |
| 4 | Filter — review period | HR | Pilih period | Data absensi/training/mentoring di-scope |
| 5 | Export CSV | HR | GET `/hr/laporan/ekspor` | File CSV download |
| 6 | CSV injection safety | HR | employee_number `=1+1` | Di-escape jadi `'=1+1` |
| 7 | Report — period scope training | HR | Filter period | Hanya training dalam rentang dates |

#### 10. Photo & Storage

| # | Skenario | Role | Langkah | Ekspektasi |
|---|----------|------|---------|------------|
| 1 | Photo — akses by owner | Employee | GET photo URL | Response file |
| 2 | Photo — akses by manager | Manager | GET photo URL subordinate | Response file |
| 3 | Photo — akses by HR | HR | GET photo URL anyone | Response file |
| 4 | Photo — 404 not found | Employee | Photo path tidak ada | 404 |
| 5 | Photo purge — command | CLI | `php artisan attendance:purge-photos --days=365` | Foto lama terhapus |
| 6 | Photo purge — after purge, 404 | Employee | Akses foto yang sudah dipurge | 404 |

#### 11. Frontend — Offline Capture Page

| # | Skenario | Perangkat | Langkah | Ekspektasi |
|---|----------|-----------|---------|------------|
| 1 | Camera capture | Mobile | Klik file input accept="image/*" | Kamera terbuka |
| 2 | Geolocation | Browser | Allow location | Koordinat terbaca |
| 3 | Watermarked photo | Browser | Selfie + submit | Foto diberi watermark nama, waktu, koordinat, lokasi |
| 4 | Offline — IndexedDB | Browser | Submit saat offline | Queue tersimpan |
| 5 | Online sync — auto | Browser | Kembali online | Queue otomatis dikirim |
| 6 | Network status indicator | Browser | Offline/online | Badge berubah |
| 7 | Photo >5MB | Browser | Upload foto besar | Error "melebihi 5 MB" |
| 8 | Watermark size — small photo | Browser | Foto resolusi rendah | Canvas scale minimal 1 |

#### 12. Keamanan

| # | Skenario | Langkah | Ekspektasi |
|---|----------|---------|------------|
| 1 | CSRF — semua form | Cek setiap POST route | Token CSRF required |
| 2 | SQL Injection | Input special chars | Query parameterized (Eloquent) |
| 3 | XSS — nama/deskripsi | Input `<script>` | Blade auto-escape |
| 4 | Throttle — login | POST /login 6x cepat | Ke-6 ditolak 429 Too Many Requests |
| 5 | **BUG: No throttle attendance** | POST store 50x | Tidak dibatasi — potensi abuse |
| 6 | Mass assignment — semua model | Cek fillable | Tidak ada kolom sensitif di fillable |
| 7 | CSV formula injection | Import CSV ke Excel | Formulas `=1+1` di-escape |

#### 13. Database & Migrasi

| # | Skenario | Langkah | Ekspektasi |
|---|----------|---------|------------|
| 1 | Run all migrations | `php artisan migrate:fresh` | Semua tabel terbuat |
| 2 | Unique constraints | Duplicate data | Error integrity constraint |
| 3 | Foreign key cascade | Hapus parent | Child terhapus/null |
| 4 | Rollback | `php artisan migrate:rollback` | Semua tabel ter-drop clean |
| 5 | Seeder — data valid | `php artisan db:seed` | Semua tabel >=5 baris |
| 6 | Seeder idempotent | Seed 2x | Tidak duplicate, updateOrInsert |

---

### C. COVERAGE TEST SAAT INI

| Area | Test File | Status |
|------|----------|--------|
| Auth & Panel Access | `tests/Feature/FilamentAccessTest.php` | ✅ 10 test |
| Duty Trip & Attendance | `tests/Feature/DutyAttendanceTest.php` | ✅ 10 test |
| Merit System | `tests/Feature/MeritSystemTest.php` | ✅ 7 test |
| Career Development | `tests/Feature/CareerDevelopmentTest.php` | ✅ 7 test |
| HR Report & Operations | `tests/Feature/OperationsReportTest.php` | ✅ 3 test |
| Database Seeder | `tests/Feature/DatabaseSeederTest.php` | ✅ 1 test |
| Unit (Example + SqliteBackup) | `tests/Unit/` | ✅ 2 test |
| **Total** | | **✅ 45 test pass** |

---

### D. RINGKASAN BUG

| ID | Nama | Severity | Area | Status |
|----|------|----------|------|--------|
| B1 | `canAttend()` vs `record()` status conflict | **Critical** | Attendance | Belum diperbaiki |
| B2 | Mentoring race condition (no locking) | **Critical** | Mentoring | Belum diperbaiki |
| B3 | Score = 0-255 tanpa validasi max | **High** | Performance Review | Belum diperbaiki |
| B4 | Trip selalu Completed meski absensi flagged | **High** | Attendance | Belum diperbaiki |
| B10 | Mentor `guardManager()` tidak fresh read | **High** | Mentoring | Belum diperbaiki |
| B5 | `reviewScore()` falsy bug | **Medium** | Merit | Belum diperbaiki |
| B6 | `captured_at` masa depan valid | **Medium** | Attendance | Belum diperbaiki |
| B7 | Tidak ada throttle attendance store | **Medium** | Route | Belum diperbaiki |
| B8 | CSV injection protection parsial | **Medium** | Report | Belum diperbaiki |
| B9 | Double inactive check (redirect loop) | **Low** | Auth | Belum diperbaiki |
