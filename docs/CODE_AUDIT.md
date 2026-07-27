# Code Audit — Putaran 1

Tanggal audit: 27 Juli 2026  
Commit yang diaudit: `beb74ee`  
Baseline: `docs/SOMETHING_NEW.md`  
Rencana: `docs/AUDIT_PLAN.md`  
Mode: read-only; tidak ada kode aplikasi yang diperbaiki pada putaran ini.

## Ringkasan eksekutif

Seluruh fitur web, resource Filament, model, service, event/listener, command terjadwal,
widget, notification, migration, dan test telah dipetakan. Audit menemukan:

| Severity | Jumlah |
| --- | ---: |
| Critical | 5 |
| High | 19 |
| Medium | 5 |
| Low | 0 |
| **Total** | **29** |

Risiko tertinggi:

1. State Filament yang `disabled` masih dipercaya server. Employee dapat membuat tugas
   luar auto-approved untuk user lain dan membuat cuti berstatus approved sendiri.
2. HR dapat menjalankan aksi Manager pada tugas luar/check-out dan dapat mengisi sampai
   mengunci appraisal tanpa Manager.
3. Mantan atau reviewer yang bukan atasan langsung tetap dapat membaca/mengubah appraisal.
4. State machine cuti, review, dan promosi dapat dilewati melalui update model biasa.
5. Data turunan attendance, merit, dan jabatan tidak selalu mengikuti perubahan sumber.

Kesimpulan putaran 1: happy path utama ada dan suite hijau, tetapi trust boundary dan
invariant workflow belum dipusatkan di server. UI hiding/disabled dan action Filament
menjadi pengaman utama; beberapa dapat dilewati lewat state request atau update model.

## Metode dan batas audit

- CodeGraph dipakai untuk source, call path, dan blast radius.
- Semua 111 file PHP di `app/` diinventarisasi.
- Semua 52 automated test dijalankan: **52 passed, 397 assertions**.
- Probe tambahan memakai database SQLite in-memory dan dua atau lebih akun untuk:
  akses panel, URL record langsung, action Filament, forged Livewire state, perubahan
  atasan, lifecycle cuti, timeout attendance, dan promosi.
- Scheduler diverifikasi dengan `CACHE_STORE=array php artisan schedule:list`.
- MySQL lokal pada `127.0.0.1:3307` tidak tersedia saat audit. Probe domain memakai
  SQLite seperti test suite; perilaku khusus MySQL belum diuji ulang.
- Kamera perangkat, permission GPS browser, Push API browser, dan service worker tidak
  diuji pada perangkat fisik. Implementasi server/view tetap diaudit.
- `composer test` ditolak Composer karena proses berjalan sebagai root dan plugin tidak
  dimuat. Perintah ekuivalen `php artisan config:clear && php artisan test` lulus.

## Peta sistem

### Portal dan aktor

| Aktor | `/app` | `/admin` | Hak aktual utama |
| --- | ---: | ---: | --- |
| Employee aktif | 200 | 403 | Presensi, tugas luar sendiri, cuti sendiri, self-review, progres IDP, baca skill/karier |
| Manager aktif | 200 | 200 | Semua hak employee; attendance/tugas tim, review, promosi |
| HR Admin aktif | 403 | 200 | Master data, cuti, attendance global, review, promosi, IDP |
| Director aktif | 403 | 200 | Review, promosi, rekap dan dashboard eksekutif |
| IT Admin aktif | 403 | 200 | CRUD user/account |
| User nonaktif | 403 | 403 | Tidak ada |

Probe akses dasar lulus:

- Employee membuka record leave/review milik user lain: 404.
- Manager membuka attendance request/review bukan bawahannya: 404 pada kondisi normal.
- Manager menjalankan action exception milik tim lain: action tidak resolvable.
- Wrong-role membuka master data: 403.
- HR/Director membuka `/admin/users`: 403; IT: 200.
- Employee membuka approved leave miliknya untuk edit: 403.

Masalah muncul ketika ownership/status dapat dipalsukan atau relasi atasan berubah.

### Alur dan status

| Fitur | Alur baseline | Implementasi aktual |
| --- | --- | --- |
| Tugas luar bottom-up | Employee pending → Manager approve/reject | HR juga dapat memproses; state ownership/flow dapat dipalsukan |
| Tugas luar top-down | Manager membuat → auto-approved | Model percaya `created_by` dan `flow_type` dari payload |
| Cuti | Employee pending → HR approve/reject | Employee/HR dapat menyuntik status; cleanup lifecycle tidak ada |
| Check-out exception | Employee pending → Manager verify → timeout final 23:59 | HR ikut verify; bukti tidak tampil; timeout dapat dibalik |
| Review | Draft → submitted oleh Manager → approved/locked | HR dapat mengisi dan menuntaskan; model mengizinkan lompatan status |
| Promosi | Proposed → HR approved → Director approved + effective date | Model mengizinkan lompatan; posisi employee tidak pernah diterapkan |
| IDP | HR kelola isi; Employee update progress | Guard owner/field lulus probe |
| Candidate pool | Job bulanan, tiga syarat, proposed 30 hari | Draft merit dapat dipakai; duplicate proposal manual dimungkinkan |

### Inventaris implementasi

**18 domain resources**

`Attendance`, `AttendanceRequest`, `BranchOffice`, `CareerPath`,
`DailyAttendanceSummary`, `Department`, `Holiday`,
`IndividualDevelopmentPlan`, `Kpi`, `LeaveRequest`, `PerformanceReview`,
`Position`, `Promotion`, `ReviewKpiDetail`, `Skill`, `User`, `UserSkill`,
`WorkSchedule`; ditambah base `RoleAwareResource`.

**19 models**

`Attendance`, `AttendanceRequest`, `BranchOffice`, `CareerPath`,
`DailyAttendanceSummary`, `Department`, `Holiday`,
`IndividualDevelopmentPlan`, `Kpi`, `LeaveRequest`, `PerformanceReview`,
`Position`, `PositionSkill`, `Promotion`, `ReviewKpiDetail`, `Skill`,
`User`, `UserSkill`, `WorkSchedule`.

**5 services**

`AttendanceService`, `AttendanceScoreService`, `GoogleMapsService`,
`MeritScoreService`, `ReadinessScoreService`.

**Event dan listener**

- `AttendanceDataChanged`
- queued listener `RecalculateMeritOnChange`

**4 commands**

- `attendance:aggregate`
- `attendance:populate-holidays`
- `career:expire-promotions`
- `career:scan-candidates`

**Jadwal aktif**

```text
23:59 setiap hari       attendance:aggregate
00:15 setiap hari       career:expire-promotions
00:30 tanggal 1         career:scan-candidates
```

Semua memakai timezone scheduler `Asia/Jakarta`.

**8 concrete notifications + 1 base**

`AttendanceRequestApproved`, `AttendanceRequestAssigned`,
`CheckOutExceptionPending`, `LeaveRequestApproved`, `LeaveRequestRejected`,
`MeritScorePublished`, `PromotionApproved`, `PromotionProposed`, dan
`WorkflowNotification`.

**10 widgets**

- Employee: `TodayAttendanceStatus`, `ActiveDutyTrips`, `LatestMeritGrade`,
  `IdpProgress`, `CareerReadiness`.
- Admin: `AttendanceDropAlert`, `CandidatePoolTable`, `HrAttendanceOverview`,
  `MeritDistribution`, `PendingApprovals`.

**Authorization**

Tidak ada `app/Policies`. Guard berada di `User::canAccessPanel`,
`RoleAwareResource`, scope `getEloquentQuery`, `can*`, visibility action,
dan disabled/dehydrated form state.

## Temuan

### AUD-001 — Critical

**Fitur:** Tugas luar bottom-up/top-down  
**Spesifikasi:** Bottom-up dibuat employee untuk dirinya dan menunggu Manager.
Top-down dibuat atasan langsung dan auto-approved atas nama atasan itu.  
**Masalah:** `user_id`, `created_by`, `flow_type`, dan `status` yang disabled tetap
`dehydrated`. Create page tidak memaksa ulang nilai server-side. Model auto-approve
hanya percaya `flow_type` dan menyalin `created_by` ke `approved_by`.  
**Dampak:** Employee dapat membuat tugas auto-approved untuk user lain. Manager dapat
memalsukan identitas creator/approver. Audit trail dan hak approval rusak.  
**Lokasi:** `app/Filament/Resources/AttendanceRequestResource.php:48-80,138-144`;
`app/Filament/Resources/AttendanceRequestResource/Pages/CreateAttendanceRequest.php:8-10`;
`app/Models/AttendanceRequest.php:77-81`.  
**Reproduksi:** Login Employee 6, kirim state Livewire `user_id=8`,
`created_by=5`, `flow_type=top_down`. Record tersimpan untuk user 8 dengan
`status=approved`, `approved_by=5`. Probe Manager dengan creator Manager lain juga
menghasilkan approver palsu.  
**Perbaikan:** Force `user_id`, `created_by`, `flow_type`, status, dan approver dari
aktor/panel di server. Model wajib menolak top-down bila
`created_by !== user.manager_id` dan bottom-up bila `created_by !== user_id`.  
**Test hilang:** Forged disabled Livewire state oleh Employee dan Manager; foreign
ownership; top-down oleh bukan atasan langsung.

### AUD-002 — Critical

**Fitur:** Cuti dan attendance score  
**Spesifikasi:** Cuti hanya approved setelah verifikasi HR, dengan `approved_by` dan
`approved_at`; hanya cuti sah yang mengurangi effective workdays.  
**Masalah:** Employee dapat mengirim `status=approved` melalui state Livewire walau
field disabled. Create page hanya memberi default jika status kosong.
`AttendanceScoreService` percaya status approved tanpa metadata approval.  
**Dampak:** Employee dapat mengecualikan hari tanpa presensi dari Alfa tanpa persetujuan
HR. Ini perubahan workflow dan kalkulasi oleh aktor tanpa hak.  
**Lokasi:** `app/Filament/Resources/LeaveRequestResource.php:42-76`;
`app/Filament/Resources/LeaveRequestResource/Pages/CreateLeaveRequest.php:13-17`;
`app/Models/LeaveRequest.php:68-72`;
`app/Services/AttendanceScoreService.php:45-58`.  
**Reproduksi:** Employee membuat leave dengan `status=approved`. Hasil:
`approved_by=NULL`, summary 0, tetapi attendance score tiga hari tanpa presensi tetap
100 karena rentang dianggap leave.  
**Perbaikan:** Force create Employee ke owner authenticated, `pending`, approver null.
Score hanya mengakui approved dengan metadata valid. Tambah invariant approval pada
model/service.  
**Test hilang:** Forged create/edit status dan `user_id`; approved tanpa approver tidak
boleh mengubah effective workdays.

### AUD-003 — Critical

**Fitur:** Approval tugas luar dan verifikasi check-out exception  
**Spesifikasi:** Manager menyetujui tugas tim dan memverifikasi check-out luar radius.
HR memonitor rekap attendance dan memverifikasi cuti.  
**Masalah:** HR mendapat akses penuh ke `AttendanceResource` dan
`AttendanceRequestResource`. Action hanya mengecek panel admin/status, bukan role dan
atasan langsung. HR menerima query global dan notification exception.  
**Dampak:** HR dapat approve/reject tugas luar dan exception seluruh employee di luar
hak blueprint.  
**Lokasi:** `app/Filament/Resources/RoleAwareResource.php:38-39`;
`app/Filament/Resources/AttendanceRequestResource.php:210-235,248-260`;
`app/Filament/Resources/AttendanceResource.php:125-160`;
`app/Models/Attendance.php:75-84`.  
**Reproduksi:** Login HR, jalankan action approve pada bottom-up milik employee.
`approved_by` menjadi HR, bukan manager employee. Action approve/reject exception juga
visible dan executable bagi HR.  
**Perbaikan:** Authorize tiap action dengan role Manager dan
`record.user.manager_id === Auth::id()`. Bila HR memang co-verifier, ubah baseline
secara eksplisit dan simpan verifier trail.  
**Test hilang:** HR, Manager tim lain, dan Manager sah memanggil action yang sama.

### AUD-004 — Critical

**Fitur:** Performance review / merit  
**Spesifikasi:** Employee mengisi self-assessment; Manager mengisi manager assessment
dan submit; HR/Direksi hanya tahap pengesahan sesuai keputusan produk.  
**Masalah:** HR dapat mengubah `manager_score`, submit, approve, lalu lock review
sendirian. Tidak ada maker-checker boundary.  
**Dampak:** Satu akun HR dapat memalsukan penilaian Manager dan memfinalkan merit,
grade, serta input promosi.  
**Lokasi:** `app/Filament/Resources/ReviewKpiDetailResource.php:53-63`;
`app/Filament/Resources/PerformanceReviewResource.php:149-181,209-223`;
`tests/Feature/KpiManagementTest.php:78-126`.  
**Reproduksi:** Login HR, buka draft, isi manager score 90, jalankan `submit`,
`approve`, `lock`. Status akhir `locked`.  
**Perbaikan:** Manager fields dan submit hanya untuk reviewer yang merupakan atasan
aktif employee. Pisahkan action approval/lock dari pengisi penilaian.  
**Test hilang:** HR tidak dapat mengubah manager fields/submit; workflow dua akun
Manager–HR/Director.

### AUD-005 — Critical

**Fitur:** Ownership performance review  
**Spesifikasi:** Manager hanya melihat/mengubah review bawahan langsung saat ini.  
**Masalah:** Scope dan edit guard memakai `reviewer_id`, bukan
`review.user.manager_id`. Setelah employee pindah atasan, reviewer lama tetap punya
akses. HR juga dapat memilih reviewer yang bukan atasan employee.  
**Dampak:** Mantan/unrelated Manager dapat membaca dan mengubah data appraisal sensitif;
Manager baru tidak otomatis dapat melanjutkan review.  
**Lokasi:** `app/Filament/Resources/PerformanceReviewResource.php:189-223`.  
**Reproduksi:** Buat review oleh Manager A, pindahkan employee ke Manager B, login A,
buka `/admin/performance-reviews/{id}/edit`; HTTP 200.  
**Perbaikan:** Scope dan guard Manager wajib memeriksa atasan langsung saat ini.
Tentukan aturan transfer ownership untuk review in-flight. Batasi reviewer selector ke
atasan employee.  
**Test hilang:** Transfer bawahan; Manager lama 403/404, Manager baru mendapat akses
sesuai aturan; reviewer asing ditolak.

### AUD-006 — High

**Fitur:** Approval cuti oleh HR  
**Spesifikasi:** Approval atomik mengisi status, approver, waktu, summary, recalculation,
dan notification.  
**Masalah:** Dropdown status dapat diedit HR langsung. Jalur ini melewati action
approval sehingga `approved_by`, `approved_at`, dan summary tidak terisi, tetapi
notification approval tetap terkirim karena status berubah.  
**Dampak:** Record tampak dan dinotifikasikan approved, tetapi attendance/merit tidak
menerima efek cuti.  
**Lokasi:** `app/Filament/Resources/LeaveRequestResource.php:71-77,182-188`;
`app/Models/LeaveRequest.php:68-108`.  
**Reproduksi:** HR edit pending menjadi approved melalui form. Hasil:
`approved_by=NULL`, `approved_at=NULL`, summary 0.  
**Perbaikan:** Status read-only; seluruh transisi melalui service/action tunggal.
Model menolak approved tanpa approver dan timestamp.  
**Test hilang:** Edit payload HR tidak boleh melompati action; action sah menghasilkan
seluruh side effect.

### AUD-007 — High

**Fitur:** Lifecycle cuti approved  
**Spesifikasi:** Summary leave dan merit harus mengikuti rentang/status approved yang
aktif. Perubahan retroaktif harus rebuild tanggal terdampak.  
**Masalah:** Model hanya menulis summary saat state saat ini approved. Rentang/user
lama tidak dibersihkan; rejection dan deletion tidak ditangani. HR dapat edit/delete
approved leave.  
**Dampak:** Rekap, attendance score, dan merit tetap menganggap leave yang sudah
dipindah, ditolak, atau dihapus.  
**Lokasi:** `app/Models/LeaveRequest.php:68-97`;
`app/Filament/Resources/LeaveRequestResource.php:162-170,182-198`;
`app/Filament/Resources/LeaveRequestResource/Pages/EditLeaveRequest.php:13-17`.  
**Reproduksi:** Approve tanggal 8–10, ubah menjadi 9–11, reject, lalu delete. Summary
tanggal 8–11 tetap `leave`.  
**Perbaikan:** Rebuild union owner/rentang lama dan baru pada update/delete, lalu
dispatch recalculation. Alternatif lebih kecil: blok edit/delete status terminal.  
**Test hilang:** Approved→ubah rentang/user, Approved→Rejected, delete approved.

### AUD-008 — High

**Fitur:** Agregasi attendance intrahari  
**Spesifikasi:** Status final harian ditentukan job pukul 23:59 WIB; pending
check-out belum menjadi `missing_checkout` sebelum cutoff.  
**Masalah:** Setiap save attendance mengirim event dengan
`aggregateAttendance=true`. Queued listener langsung menjalankan aggregator. Check-in
tanpa check-out dan exception pending segera ditulis `missing_checkout`, lalu merit
draft ikut dihitung ulang.  
**Dampak:** Dashboard dan merit dapat turun sementara sebelum hari kerja selesai;
status final muncul terlalu dini.  
**Lokasi:** `app/Models/Attendance.php:67-68,88-92`;
`app/Listeners/RecalculateMeritOnChange.php:19-30`;
`app/Console/Commands/AggregateDailyAttendance.php:83-98`.  
**Reproduksi:** Simpan check-in normal sebelum jam pulang dan proses queue. Listener
mengagregasi hari yang sama; karena belum ada check-out, summary menjadi
`missing_checkout`.  
**Perbaikan:** Pisahkan live/incomplete state dari final daily aggregation. Jangan
finalkan missing checkout sebelum cutoff; recalculate merit setelah summary final atau
setelah checkout sah.  
**Test hilang:** Freeze waktu pagi/sore; check-in atau pending exception tidak boleh
menjadi missing sebelum 23:59.

### AUD-009 — High

**Fitur:** Timeout exception 23:59  
**Spesifikasi:** Pending pada 23:59 menjadi final `missing_checkout`; rejected/timeout
mendapat penalti.  
**Masalah:** Aggregator hanya mengubah summary. Record attendance tetap
`pending_verification`; action tetap dapat approve esok hari dan event mengagregasi
ulang menjadi present.  
**Dampak:** Penalti timeout dapat dihapus setelah batas final.  
**Lokasi:** `app/Console/Commands/AggregateDailyAttendance.php:89-98`;
`app/Filament/Resources/AttendanceResource.php:125-144`;
`app/Models/Attendance.php:67-68,88-92`.  
**Reproduksi:** 23:59 menghasilkan summary `missing_checkout`; 00:05 approve exception;
summary berubah menjadi `present`, checkout `normal`.  
**Perbaikan:** Job cutoff mengubah pending ke status terminal timeout/rejected. Action
menolak tanggal yang sudah ditutup.  
**Test hilang:** Approve setelah cutoff gagal dan summary tetap missing.

### AUD-010 — High

**Fitur:** Urutan check-in/check-out  
**Spesifikasi:** Check-out menutup sesi yang sudah check-in pada hari/session yang sama.  
**Masalah:** Service tidak mencari pasangan check-in. Check-out tanpa check-in,
sebelum check-in, dan duplicate checkout dapat diterima. Existing test memakai DB
kosong dan menganggap checkout sukses.  
**Dampak:** Log sesi invalid tersimpan; aggregator menghasilkan Alfa/missing yang tidak
merepresentasikan aktivitas sebenarnya.  
**Lokasi:** `app/Services/AttendanceService.php:42-48,89-125`;
`app/Models/Attendance.php:49-65`;
`tests/Feature/AttendanceServiceTest.php:256-317`.  
**Reproduksi:** Panggil `AttendanceService::record(CheckOut)` pada DB tanpa check-in;
record checkout berhasil dibuat.  
**Perbaikan:** Checkout wajib menemukan check-in lebih awal pada `user/date/session_key`
yang sama dan belum ditutup.  
**Test hilang:** Checkout tanpa check-in, sebelum check-in, dan checkout kedua.

### AUD-011 — High

**Fitur:** Agregasi hari kerja  
**Spesifikasi:** Alfa hanya untuk hari kerja efektif; holiday/leave dikecualikan.  
**Masalah:** Aggregator membuat summary untuk semua hari kalender dan tidak memeriksa
weekend. Attendance score kemudian membuang weekend, sehingga summary/dashboard dan
score memakai semesta tanggal berbeda.  
**Dampak:** Sabtu/Minggu muncul sebagai Alfa palsu pada recap dan widget.  
**Lokasi:** `app/Console/Commands/AggregateDailyAttendance.php:32-41,83-86`;
`app/Services/AttendanceScoreService.php:63-69`;
`tests/Feature/DailyAttendanceAggregationTest.php:126-132`.  
**Reproduksi:** Aggregate `2026-08-01` (Sabtu) tanpa holiday. Test saat ini
mengonfirmasi summary `alfa`.  
**Perbaikan:** Definisikan kalender kerja. Dengan baseline sekarang, skip weekend
sebelum membuat summary kecuali ada schedule hari kerja eksplisit.  
**Test hilang:** Weekend tidak membuat Alfa; kalender kerja khusus bila diperlukan.

### AUD-012 — High

**Fitur:** Live Selfie dan keputusan exception  
**Spesifikasi:** Presensi dan exception memakai Live Selfie; verifier menilai alasan dan
foto.  
**Masalah:** Form memakai generic image upload tanpa camera capture; file galeri/lama
diterima. Resource admin tidak menampilkan `photo_path` atau `exception_reason` sebelum
approve/reject. Asset `public/js/face-verification.js` tidak dipakai.  
**Dampak:** Kontrol anti-fraud tidak ada dan verifier mengambil keputusan tanpa bukti.  
**Lokasi:** `app/Filament/Resources/AttendanceResource.php:67-79,83-145`;
`resources/views/filament/forms/components/gps-capture.blade.php:1-34`;
`public/js/face-verification.js`;
`tests/Feature/AttendanceServiceTest.php:323-335`.  
**Reproduksi:** Pilih file gambar dari storage pada field photo; form menerima. Buka
daftar exception sebagai Manager; tabel/action tidak menampilkan alasan/foto.  
**Perbaikan:** Gunakan camera-facing capture dan preview bukti terotorisasi pada
view/modal action. Tetap lakukan validasi server-side ukuran/MIME.  
**Test hilang:** Field meminta capture kamera; Manager sah melihat bukti; foreign
Manager tidak dapat membuka file.

### AUD-013 — High

**Fitur:** Lifecycle tugas luar approved  
**Spesifikasi:** Data yang telah disetujui menjadi dasar geofence, jadwal, attendance,
dan audit trail. Perubahan material perlu approval ulang.  
**Masalah:** Semua admin yang mendapat resource dapat edit dan bulk-delete record
approved/rejected. Edit tidak mereset status atau meminta approval ulang.  
**Dampak:** Tujuan, koordinat, radius, atau jadwal dapat berubah setelah approval;
record audit dapat dihapus sebelum dipakai.  
**Lokasi:** `app/Filament/Resources/AttendanceRequestResource.php:237-245,263-279`;
`app/Filament/Resources/AttendanceRequestResource/Pages/EditAttendanceRequest.php:13-17`.  
**Reproduksi:** Login Manager, buka approved request, ubah koordinat/tanggal; save tetap
approved. Header delete dan bulk delete tetap tersedia.  
**Perbaikan:** Pending saja yang editable/deletable. Perubahan approved membuat revisi
pending baru atau mereset approval secara atomik.  
**Test hilang:** Edit/delete approved/rejected ditolak; perubahan material wajib
reapproval.

### AUD-014 — High

**Fitur:** Notification workflow  
**Spesifikasi:** Transisi approval/verifikasi mengirim database + web-push notification.  
**Masalah:** Tidak ada notification untuk attendance request rejected/cancelled,
hasil approve/reject checkout exception kepada employee, promotion rejected, atau
handoff HR→Director. Model hanya menangani sebagian transisi positif.  
**Dampak:** Aktor tidak tahu keputusan atau pekerjaan berikutnya; workflow dapat macet
meski status database berubah.  
**Lokasi:** `app/Models/AttendanceRequest.php:84-96`;
`app/Models/Attendance.php:70-85`;
`app/Models/Promotion.php:16-35`;
`app/Notifications/`.  
**Reproduksi:** Reject bottom-up atau exception; tidak ada notification class/call ke
employee. Approve HR/reject promotion juga tidak mengirim handoff/hasil terkait.  
**Perbaikan:** Definisikan recipient tiap transisi dan kirim dari transition service
tunggal, bukan dari action UI.  
**Test hilang:** Matriks seluruh status transition × recipient × database/webpush.

### AUD-015 — High

**Fitur:** Registrasi Web Push/PWA  
**Spesifikasi:** Notification memakai database + web push untuk user aktif.  
**Masalah:** Alias middleware `active` menunjuk
`App\Http\Middleware\EnsureUserIsActive`, tetapi class/file tidak ada. Route subscribe
dan unsubscribe memakai alias itu. Browser menangkap error secara diam-diam.  
**Dampak:** Endpoint push subscription menghasilkan 500; tidak ada browser yang dapat
mendaftarkan endpoint, sehingga channel webpush praktis tidak berfungsi.  
**Lokasi:** `bootstrap/app.php:3,15`; `routes/web.php:14-16`;
`resources/views/pwa/register.blade.php:1-33`.  
**Reproduksi:** `class_exists('App\Http\Middleware\EnsureUserIsActive')` menghasilkan
`false`; POST authenticated ke route ber-middleware `active` gagal resolve class.  
**Perbaikan:** Gunakan middleware aktif yang benar atau buat class minimum dan uji
route. Jangan swallow error registrasi tanpa telemetry.  
**Test hilang:** POST subscribe/unsubscribe user aktif/nonaktif; browser subscription
smoke test.

### AUD-016 — High

**Fitur:** State machine performance review  
**Spesifikasi:** `draft → submitted → approved → locked`; submission wajib seluruh
manager score terisi dan total bobot 100.  
**Masalah:** Validasi hanya berjalan saat status tepat berubah ke `submitted`.
`draft → approved` langsung diterima dan mengirim notification publikasi; transition
lain juga tidak dijaga model.  
**Dampak:** Review kosong/default dapat dipublikasikan tanpa manager assessment dan
kalkulasi sah.  
**Lokasi:** `app/Models/PerformanceReview.php:16-45`;
`tests/Feature/WorkflowNotificationsTest.php:76-85`.  
**Reproduksi:** `$review->update(['status' => ReviewStatus::Approved])` dari draft;
update berhasil dan employee dinotifikasi.  
**Perbaikan:** Pusatkan transition guard pada model/service; approve hanya dari
submitted, lock hanya dari approved.  
**Test hilang:** Semua transition ilegal melalui raw model update ditolak.

### AUD-017 — High

**Fitur:** Publikasi merit dan candidate pool  
**Spesifikasi:** Employee/direksi memakai merit yang sudah disahkan; candidate memakai
grade merit sah.  
**Masalah:** Recalculation memproses semua review selain locked. Widget merit,
distribution, dan candidate scan menerima semua grade non-null tanpa status
published.  
**Dampak:** Employee melihat appraisal draft; grade mutable memengaruhi dashboard dan
membuat proposal promosi.  
**Lokasi:** `app/Listeners/RecalculateMeritOnChange.php:25-30`;
`app/Services/MeritScoreService.php:20-40`;
`app/Filament/Widgets/Employee/LatestMeritGrade.php:15-19`;
`app/Filament/Widgets/Admin/MeritDistribution.php:24-30`;
`app/Console/Commands/ScanCandidatePool.php:30-33`.  
**Reproduksi:** Review tetap draft, event attendance menghasilkan grade B/final 84.
Widget Employee menampilkan B; scan membuat satu promotion candidate.  
**Perbaikan:** Tambah scope reusable `published()` sesuai keputusan status
`approved|locked`; pakai pada widget, chart, dan scanner.  
**Test hilang:** Draft/submitted tidak tampil/tidak membuat candidate; published tampil.

### AUD-018 — High

**Fitur:** State machine promosi  
**Spesifikasi:** `proposed → approved_by_hr → approved_by_director`; final wajib
effective date.  
**Masalah:** Model tidak memvalidasi transition. Raw update
`proposed → approved_by_director` diterima tanpa HR/effective date dan mengirim
notification final.  
**Dampak:** Final sign-off dapat tercatat tanpa verifikasi HR dan tanggal berlaku.  
**Lokasi:** `app/Models/Promotion.php:16-35,45-59`;
`app/Filament/Resources/PromotionResource.php:102-138`;
`tests/Feature/WorkflowNotificationsTest.php:87-95`.  
**Reproduksi:** `$promotion->update(['status' =>
PromotionStatus::ApprovedByDirector])`; update dan notification berhasil.  
**Perbaikan:** Transition method/guard server-side; final approval wajib berasal dari
HR-approved dan memiliki effective date.  
**Test hilang:** Raw illegal transition ditolak. Action Filament skip-HR sudah lulus
probe.

### AUD-019 — High

**Fitur:** Proposal promosi manual  
**Spesifikasi:** Satu lifecycle proposal aktif per employee/target; expired dapat
didaur ulang.  
**Masalah:** Create manual tidak mengecek proposal aktif dan database tidak punya
constraint yang mencegah duplicate.  
**Dampak:** Candidate pool, notification HR, dan approval ganda untuk promosi sama.  
**Lokasi:** `app/Filament/Resources/PromotionResource/Pages/ListPromotions.php:22-40`;
`database/migrations/2026_07_26_000005_create_career_tables.php:32-42`.  
**Reproduksi:** Manager menjalankan create dua kali untuk employee dan target sama;
dua record `proposed` tersimpan.  
**Perbaikan:** Cek active lifecycle dalam transaksi dan tambahkan constraint/strategi
DB yang menjamin keunikan aktif.  
**Test hilang:** Create kedua gagal; record expired tetap boleh recycle.

### AUD-020 — High

**Fitur:** Penerapan promosi  
**Spesifikasi:** Final approval dengan effective date harus mengubah posisi employee dan
menjadi sumber readiness/path berikutnya.  
**Masalah:** Director action hanya mengubah status/effective date. Tidak ada service,
model event, atau scheduled command yang mengubah `users.position_id`.  
**Dampak:** Promosi tidak pernah berlaku; employee dan modul karier tetap memakai
posisi lama. Approved record juga dapat memblokir lifecycle berikutnya.  
**Lokasi:** `app/Filament/Resources/PromotionResource.php:121-126`;
`app/Models/Promotion.php:30-35`; `routes/console.php:9-15`.  
**Reproduksi:** Approve dengan effective date lampau. Status menjadi
`approved_by_director`, tetapi `users.position_id` tidak berubah.  
**Perbaikan:** Transaksi langsung untuk tanggal hari ini/lampau; job idempotent harian
untuk effective date masa depan.  
**Test hilang:** Effective date lampau/hari ini/masa depan; idempotensi; readiness
berpindah ke path baru.

### AUD-021 — High

**Fitur:** Snapshot KPI review  
**Spesifikasi:** Satu detail per pasangan review–KPI.  
**Masalah:** Tidak ada unique `(performance_review_id, kpi_id)`. Snapshot otomatis
dan create manual dapat menghasilkan duplicate; merit menjumlahkan semuanya.  
**Dampak:** Submission macet karena total bobot lebih dari 100 atau final merit
terhitung ganda.  
**Lokasi:** `database/migrations/2026_07_26_000004_create_merit_tables.php:34-45`;
`app/Models/PerformanceReview.php:32-39`;
`app/Services/MeritScoreService.php:20-22`;
`tests/Feature/BlueprintModelsTest.php:144-164`.  
**Reproduksi:** Buat review setelah KPI ada, lalu create detail dengan KPI sama; kedua
record diterima.  
**Perbaikan:** Unique DB constraint; snapshot memakai `updateOrCreate` atau
`firstOrCreate`.  
**Test hilang:** Duplicate ditolak dan skor memakai tepat satu detail.

### AUD-022 — High

**Fitur:** Master KPI  
**Spesifikasi:** Snapshot KPI wajib total bobot 100 agar review dapat disubmit.  
**Masalah:** Form hanya memvalidasi tiap bobot 0–100, bukan total master. Error baru
muncul saat review disubmit.  
**Dampak:** Satu create/edit/delete KPI dapat memblokir seluruh review baru.  
**Lokasi:** `app/Filament/Resources/KpiResource.php:41-47,72-75`;
`app/Models/PerformanceReview.php:21-30`.  
**Reproduksi:** Saat total master 100, HR tambah KPI bobot 1. Buat review, isi semua
manager score, submit; exception “Total bobot KPI harus 100”.  
**Perbaikan:** Validasi total master sebelum create/edit/delete; gunakan transaksi.  
**Test hilang:** Mutasi KPI yang menghasilkan total selain 100 ditolak.

### AUD-023 — High

**Fitur:** Struktur atasan account  
**Spesifikasi:** Employee memerlukan atasan langsung untuk bottom-up approval, review,
dan proposal promosi.  
**Masalah:** `manager_id` nullable dan form/model menerima Employee aktif tanpa
Manager.  
**Dampak:** Tugas bottom-up tidak muncul pada Manager mana pun; review/promosi tidak
punya aktor alami.  
**Lokasi:** `app/Filament/Resources/UserResource.php:42-55`;
`database/migrations/2026_07_26_000001_add_blueprint_fields_to_users.php:31`;
`app/Models/User.php:31-42`;
`tests/Feature/MasterDataResourcesTest.php:133`.  
**Reproduksi:** IT membuat user role Employee aktif dengan `manager_id=NULL`; record
diterima.  
**Perbaikan:** `manager_id` wajib secara kondisional untuk Employee; nullable hanya
untuk role lain.  
**Test hilang:** Create/update Employee tanpa Manager ditolak.

### AUD-024 — High

**Fitur:** User skill dan readiness  
**Spesifikasi:** Satu current level per pasangan employee–skill.  
**Masalah:** Form dan DB tidak menjamin unique `(user_id, skill_id)`.
`ReadinessScoreService` memakai keyed `pluck`; duplicate terakhir menang berdasarkan
urutan query.  
**Dampak:** Readiness dan kelayakan candidate nondeterministik.  
**Lokasi:** `app/Filament/Resources/UserSkillResource.php:33-59`;
`database/migrations/2026_07_26_000002_create_user_skills_table.php:11-17`;
`app/Services/ReadinessScoreService.php:20-30`.  
**Reproduksi:** Buat dua row skill sama dengan level 5 lalu 1. Readiness 20%; hapus
row terakhir, readiness menjadi 100%.  
**Perbaikan:** Unique DB constraint dan scoped unique validation/upsert pada form.  
**Test hilang:** Duplicate create/edit ditolak; score deterministik.

### AUD-025 — Medium

**Fitur:** Jalur karier Employee  
**Spesifikasi:** Employee dapat melihat jalur karier dan readiness menuju target.  
**Masalah:** Employee panel tidak menyediakan list `CareerPath`; widget mengambil
`first()` saat beberapa target berasal dari posisi yang sama.  
**Dampak:** Employee hanya melihat target arbitrer dan kehilangan alternatif jalur.  
**Lokasi:** `app/Filament/Widgets/Employee/CareerReadiness.php:19-30`;
`tests/Feature/PanelArchitectureTest.php:27-42`.  
**Reproduksi:** Buat dua career path dari posisi sama; widget hanya menampilkan satu.  
**Perbaikan:** Tampilkan read-only list semua path posisi employee; HR tetap editor
tunggal.  
**Test hilang:** Dua target tampil; path posisi lain tidak tampil.

### AUD-026 — Medium

**Fitur:** Status attendance Employee  
**Spesifikasi:** Office dan tiap duty trip adalah session terpisah.  
**Masalah:** Widget “hari ini” mengambil check-in paling awal dan checkout paling akhir
lintas semua session. Session yang belum lengkap tidak terlihat.  
**Dampak:** UI dapat tampak selesai karena office complete padahal duty session belum
check-in/out.  
**Lokasi:** `app/Filament/Widgets/Employee/TodayAttendanceStatus.php:17-34`;
`app/Filament/Widgets/Employee/ActiveDutyTrips.php:21-30`.  
**Reproduksi:** Buat office dan duty pada hari sama, hanya office lengkap. Widget
menggabungkan timestamp, bukan menampilkan status per session.  
**Perbaikan:** Tampilkan minimal office dan duty aktif terpisah, berbasis `session_key`.  
**Test hilang:** Multi-session dengan salah satu session incomplete.

### AUD-027 — Medium

**Fitur:** Portal IT Admin  
**Spesifikasi:** IT mengelola account, roles/permissions, system logs, Docker, dan API
keys.  
**Masalah:** Implementasi hanya memiliki `UserResource`. Tidak ada resource/model/route
untuk permissions, system logs, Docker operation, atau API keys.  
**Dampak:** Sebagian besar use case IT pada blueprint tidak tersedia di web.  
**Lokasi:** `app/Filament/Resources/UserResource.php`;
`app/Filament/Resources/RoleAwareResource.php:59`; route admin hasil
`php artisan route:list`.  
**Reproduksi:** Login IT; navigation hanya menyediakan user/account untuk domain IT.
Route list tidak memuat fitur lainnya.  
**Perbaikan:** Konfirmasi mana yang benar-benar wajib berada di web. Implementasi satu
per satu; jangan membuat generic admin console tanpa trust boundary.  
**Test hilang:** Route/access test tiap fitur IT yang disepakati.

### AUD-028 — Medium

**Fitur:** Timezone deployment  
**Spesifikasi:** Laravel, database container, dan server OS konsisten
`Asia/Jakarta`; cutoff 23:59 WIB bergantung padanya.  
**Masalah:** Laravel dan `.env.example` sudah benar, tetapi `compose.yaml` tidak
mengatur `TZ` atau MySQL timezone. Repo juga tidak menyediakan konfigurasi server OS.  
**Dampak:** Timestamp DB/server dapat berbeda dari scheduler/app; cutoff, effective
date, dan audit timestamp berisiko bergeser saat deployment.  
**Lokasi:** `config/app.php:68`; `.env.example:6`; `compose.yaml:1-18`.  
**Reproduksi:** Cari `TZ`, `default-time-zone`, atau `date.timezone` pada
`compose.yaml`; tidak ada.  
**Perbaikan:** Set timezone container/database secara eksplisit dan tambahkan smoke
check app/DB/current time.  
**Test hilang:** Deployment check membandingkan timezone PHP, Laravel, DB, dan
scheduler.

### AUD-029 — Medium

**Fitur:** Master Work Schedule  
**Spesifikasi:** Jadwal kerja menjadi sumber check-in, jam pulang, late tolerance, dan
Alfa cutoff yang valid.  
**Masalah:** Form hanya mewajibkan dua waktu; tidak memvalidasi
`check_out_time > check_in_time` atau mendefinisikan overnight shift.  
**Dampak:** HR dapat membuat jadwal terbalik yang membuat batas sesi dan status
attendance menyesatkan.  
**Lokasi:** `app/Filament/Resources/WorkScheduleResource.php:30-61`;
`app/Models/WorkSchedule.php`.  
**Reproduksi:** Isi check-in 17:00 dan check-out 08:00; tidak ada rule/model invariant
yang menolak.  
**Perbaikan:** Putuskan dukungan overnight. Jika tidak didukung, validasi checkout
setelah check-in; bila didukung, simpan flag/day offset eksplisit.  
**Test hilang:** Jadwal terbalik ditolak atau overnight dihitung benar.

## Ambiguitas spesifikasi yang perlu keputusan sebelum putaran 2

1. **HR pada attendance.** Blueprint menyebut HR memonitor rekap, sedangkan kode
   menjadikannya co-approver tugas luar dan exception. Tentukan read-only atau
   co-verifier resmi.
2. **Maker-checker merit.** Blueprint jelas menempatkan manager score pada Manager,
   tetapi belum menyebut aktor tepat untuk `approved` dan `locked`. Tentukan HR,
   Director, atau keduanya serta urutannya.
3. **Transfer atasan.** Tentukan apakah review/promosi historis tetap terlihat oleh
   proposer/reviewer lama, dan siapa melanjutkan record in-flight.
4. **Proposal manual Manager.** Tiga syarat readiness/grade/tenure eksplisit untuk job
   bulanan. Belum jelas apakah create manual Manager wajib memenuhi syarat sama.
5. **Kewenangan data user.** IT saat ini mengubah position, shift, branch, manager,
   join date, status, dan role; HR tidak dapat membuka `UserResource`. Pisahkan
   credential/access milik IT dari atribut organisasi milik HR atau nyatakan IT sebagai
   owner semuanya.
6. **Hari kerja.** Blueprint memakai “hari kerja efektif”, tetapi `WorkSchedule` tidak
   menyimpan hari aktif. Tegaskan pola Senin–Jumat atau kalender shift per employee.
7. **Cuti/tugas status terminal.** Tegaskan apakah approved/rejected boleh diedit,
   dibatalkan, atau dihapus; tentukan audit retention.
8. **Effective promotion.** Tegaskan perubahan position terjadi tepat effective date,
   termasuk tanggal lampau dan future scheduling.
9. **Recipient notification.** Tentukan penerima untuk rejection/cancellation,
   exception resolution, HR approval promotion, dan Director rejection.
10. **Live Selfie.** Tegaskan apakah wajib kamera real-time saja, apakah fallback upload
    diizinkan, serta retention/access-control foto.

## Cakupan test dan gap

| Area | Test yang ada | Gap utama |
| --- | --- | --- |
| Panel/RBAC | `PanelArchitectureTest` | HTTP direct-record/action dua akun, forged state, transfer atasan, field whitelist |
| Attendance service | `AttendanceServiceTest`, `GeoDistanceTest` | Urutan session, pre-cutoff finalization, camera capture, verifier evidence |
| Daily aggregation | `DailyAttendanceAggregationTest` | Weekend yang benar, timeout terminal, queue timing, retroactive union |
| Attendance score | `AttendanceScoreServiceTest` | Approved leave wajib metadata sah |
| Tugas luar/cuti model | `BlueprintModelsTest`, notification test | Employee state forgery, approved mutation/delete, rejection notification |
| Merit | `MeritScoreServiceTest`, `KpiManagementTest` | State machine, HR maker-checker, ownership transfer, duplicate KPI detail |
| Career | `ReadinessScoreServiceTest`, `CareerAutomationTest` | Published-grade scope, duplicate proposal, position effective-date job, multi-path |
| Master data | `MasterDataResourcesTest` | Aggregate KPI weight, unique user-skill, required manager, schedule time invariant |
| Dashboard | `DashboardWidgetsTest` | Draft leakage, multi-session, multiple career targets, stale source data |
| Notification | `WorkflowNotificationsTest` | Test saat ini memakai `Notification::fake`; endpoint subscription dan seluruh transition belum diuji |
| PWA/browser | Tidak ada | Service worker, push subscribe, permission GPS/kamera, offline/error behavior |
| Scheduler/deployment | Tidak ada | Timezone parity, queue worker, cron invocation, command idempotency |
| IT Admin | Access `UserResource` saja | Missing-feature routes dan pemisahan field IT/HR |

Beberapa test saat ini mengunci perilaku yang salah:

- `AttendanceServiceTest.php:256-317` menganggap checkout tanpa check-in valid.
- `DailyAttendanceAggregationTest.php:126-132` menganggap Sabtu tanpa data sebagai Alfa.
- `WorkflowNotificationsTest.php:76-95` melompati state review dan promosi langsung
  lewat model.
- `MasterDataResourcesTest.php:133` membuat Employee tanpa Manager.
- `BlueprintModelsTest.php:144-164` dapat menambah detail KPI duplicate setelah
  snapshot.

## Perilaku yang sesuai baseline

Bagian berikut diperiksa dan tidak menghasilkan finding:

- Panel inactive-user guard dan pemisahan portal dasar.
- Scope own-data Employee dan subordinate Manager pada kondisi relasi normal.
- Server-side Haversine dan penyimpanan jarak.
- Batas check-in maksimal 90 menit lebih awal, tolerance, dan Alfa cutoff.
- Duplicate check-in dilindungi model dan unique DB session key.
- Overlap tugas luar serta overlap tugas luar–cuti.
- Multi-day duty dan cabang check-out hari terakhir.
- Priority holiday/leave → duty → office pada aggregator.
- Leave approved melalui action sah membuat daily summaries.
- Formula attendance score pada baseline: `missing_checkout` juga tidak dihitung
  sebagai present, sehingga satu kasus dapat menerima penalti missing dan Alfa. Hasil
  85 pada test sesuai rumus tertulis, walau aturan ini sebaiknya dikonfirmasi produk.
- Mandatory manager score dan total snapshot KPI 100 ketika transisi benar-benar
  melewati `submitted`.
- Locked review tidak direcalculate kecuali force.
- Readiness formula, grade ordering, tenure, candidate 30 hari, dan expiry hanya
  `proposed`.
- HR-approved promotion tidak ikut expiry.
- IDP Employee hanya dapat mengubah progress miliknya; forged owner/mentor/title/status
  ditolak.
- UserSkill Employee read-only.
- Holiday create/update/delete membangun ulang summary lama dan baru.
- Director tidak dapat melewati HR melalui action Filament; celah ada pada model
  transition, bukan action UI.

## Urutan perbaikan yang disarankan untuk putaran 2

1. Tutup forged ownership/status: AUD-001 dan AUD-002.
2. Tegakkan authorization action/record: AUD-003 sampai AUD-005.
3. Pusatkan state machine dan side effect: AUD-006, AUD-007, AUD-013,
   AUD-016, AUD-018, AUD-020.
4. Benahi finalisasi attendance: AUD-008 sampai AUD-011.
5. Pulihkan bukti/notifikasi delivery: AUD-012, AUD-014, AUD-015.
6. Tambah constraint data/kalkulasi: AUD-017, AUD-019, AUD-021 sampai AUD-024.
7. Selesaikan UX/config/product decision: AUD-025 sampai AUD-029 dan daftar
   ambiguitas.

Setiap perbaikan harus dimulai dengan regression test dari bagian “Reproduksi”, lalu
memperbaiki jalur bersama terkecil. Jangan menambah policy/abstraction generik sebelum
invariant konkret di atas membutuhkan reuse.

## Hasil putaran 2

Status implementasi:

| Temuan | Status | Bukti utama |
| --- | --- | --- |
| AUD-001 | Selesai | Actor, owner, flow, status, dan approver dipaksa server-side; invariant model dan forged-state test ditambah. |
| AUD-002 | Selesai | Create cuti selalu milik actor dan pending; approval metadata wajib; score mengabaikan approval invalid. |
| AUD-003 | Selesai | Action tugas luar dan exception hanya untuk Manager langsung; HR read-only. |
| AUD-004 | Selesai | Manager assessment/submit dibatasi ke Manager langsung; HR approve; Director lock. |
| AUD-005 | Selesai | Scope dan edit guard memakai Manager employee saat ini; reviewer non-locked diselaraskan. |
| AUD-006 | Selesai | Status cuti read-only; transisi melalui method atomik `approve()`/`reject()`. |
| AUD-007 | Selesai | Cuti berstatus terminal tidak dapat diedit atau dihapus. |
| AUD-008 | Selesai | Check-in/pending exception tidak difinalkan; agregasi live hanya setelah checkout sah/keputusan. |
| AUD-009 | Selesai | Cutoff mengubah pending exception ke rejected terminal; keputusan setelah tanggal tutup ditolak. |
| AUD-010 | Selesai | Checkout wajib punya check-in lebih awal pada session sama dan belum punya checkout. |
| AUD-011 | Selesai | Agregator melewati weekend berdasarkan baseline Senin–Jumat. |
| AUD-012 | Selesai | Input meminta kamera depan, MIME/size divalidasi, bukti private tampil pada scope verifier. |
| AUD-013 | Selesai | Tugas luar terminal tidak dapat diedit atau dihapus. |
| AUD-014 | Selesai | Notifikasi rejection/cancellation, exception result, promotion handoff/result ditambah. |
| AUD-015 | Selesai | Middleware user aktif dibuat; route subscribe/unsubscribe dan telemetry PWA diuji. |
| AUD-016 | Selesai | State machine review dipaksa `draft → submitted → approved → locked`, termasuk raw model update/create. |
| AUD-017 | Selesai | Scope `published()` dipakai widget, distribusi merit, dan candidate scanner. |
| AUD-018 | Selesai | State machine promotion dan effective date dipaksa pada model. |
| AUD-019 | Selesai | Lifecycle aktif unik dijaga model dan unique constraint nullable. |
| AUD-020 | Selesai | Promosi due diterapkan atomik/idempotent; command harian menangani tanggal masa depan. |
| AUD-021 | Selesai | Unique `(performance_review_id, kpi_id)` dan snapshot `firstOrCreate`. |
| AUD-022 | Selesai | Total master KPI dijaga; review hanya dapat dibuat saat total tepat 100. |
| AUD-023 | Selesai | Employee aktif wajib memiliki Manager aktif pada form dan model. |
| AUD-024 | Selesai | Unique `(user_id, skill_id)` dijaga pada form dan database. |
| AUD-025 | Selesai | Employee mendapat list read-only dan readiness untuk semua jalur dari posisi saat ini. |
| AUD-026 | Selesai | Widget menampilkan status office dan tiap duty session secara terpisah. |
| AUD-027 | Keputusan produk | Konsol Docker/log/API key tidak dibuat tanpa daftar operasi, secret masking, audit trail, dan trust boundary host. `UserResource` tetap mencakup account/role. |
| AUD-028 | Selesai | Container/PHP/MySQL timezone dikonfigurasi; command smoke check dan scheduler assertion ditambah. MySQL live check dijalankan saat deployment. |
| AUD-029 | Selesai | Overnight tidak didukung; checkout wajib setelah check-in pada form dan model. |

Keputusan atas ambiguitas:

1. HR hanya memonitor attendance; verifier tetap Manager langsung.
2. Review memakai urutan Manager submit, HR approve, Director lock.
3. Review non-locked mengikuti Manager employee saat ini; locked mempertahankan reviewer historis.
4. Proposal manual Manager tidak mengambil kriteria job bulanan; duplicate lifecycle aktif tetap ditolak.
5. IT tetap owner `UserResource`; pemisahan atribut organisasi ke HR memerlukan keputusan produk terpisah.
6. Kalender kerja default Senin–Jumat sampai schedule menyimpan hari aktif.
7. Cuti dan tugas luar terminal immutable dan tidak dapat dihapus.
8. Promosi diterapkan tepat pada `effective_date`, termasuk tanggal lampau dan masa depan.
9. Hasil workflow dikirim kepada employee/proposer; handoff HR promotion dikirim kepada Director aktif.
10. Live Selfie memakai kamera real-time dan private storage; gallery fallback tidak disediakan.

Verifikasi akhir:

- `php artisan test`: 83 test lulus, 622 assertion.
- `./vendor/bin/pint --test`, `git diff --check`, `npm run build`, dan
  `docker compose config --quiet`: lulus.
- Scheduler memuat agregasi attendance serta expire/apply/scan promotion.
- Smoke check timezone Laravel/PHP/SQLite lulus; koneksi MySQL live diverifikasi saat
  deployment karena instance lokal tidak tersedia.
