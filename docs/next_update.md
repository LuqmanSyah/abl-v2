# Next Update — Roadmap Pengembangan

## Prioritas: Dashboard Lanjutan ✅

---

## Sprint 1: Notifikasi

### Tujuan

Tidak ada sistem notifikasi saat ini. Employee, Manager, HR tidak mendapat alert untuk event penting. Implementasi notifikasi in-app (Filament Notification) + email.

### Fitur

**Employee:**
- Trip baru ditugaskan → notifikasi + email
- Absen teringat: notifikasi jika hari ini ada trip tapi belum absen
- Mentoring dijadwalkan → notifikasi
- Merit publish → notifikasi

**Manager:**
- Absensi bawahan perlu review (NeedsReview) → notifikasi
- Ada mentoring baru perlu approve → notifikasi
- KPI perlu diisi (deadline approaching)
- Training request pending

**HR:**
- Ada absensi NeedsReview → notifikasi
- Merit siap diverifikasi
- User nonaktif / perubahan data penting

### Detail Teknis

**Stack:**
- In-app: `Filament\Notifications\Notification` (database + broadcast)
- Email: Laravel Mail + queue job
- Broadcast opsional: Pusher / Laravel Reverb untuk real-time

**Tabel baru:**
```sql
notifications — Laravel default (database notifications)

migration: create_notifications_table (built-in)
```

**Job:**
```
SendTripAssignedNotification  → queue
SendMentoringReminder         → queue
SendAttendanceNeedsReview     → queue
SendMeritPublished            → queue
AttendanceReminder            → scheduler tiap jam 08:00
```

### Test Plan
- Notifikasi muncul di panel penerima
- Email terkirim
- Notifikasi tidak dikirim ke role yang tidak berhak
- Queue job terproses benar

---

## Sprint 2: Scheduler Otomatis

### Tujuan

Saat ini merit dihitung manual oleh HR via panel. Banyak operasi rutin belum otomatis. Implementasi cron scheduler untuk operasi periodik.

### Fitur

**Hitung Merit Akhir Bulan:**
- Cron: tiap tanggal 1 pukul 00:05
- Hitung merit untuk semua ReviewPeriod aktif + employee dengan KPI lengkap
- Lewati employee yang sudah punya merit terpublikasi di periode sama
- Log hasil: berapa employee dihitung, berapa gagal

**KPI Deadline Reminder:**
- Cron: tiap hari pukul 09:00, mulai 7 hari sebelum KPI deadline
- Kirim notifikasi ke manager yang belum input KPI

**Absensi Reminder:**
- Cron: tiap jam 08:00 dan 12:00
- Cek employee yang punya trip hari ini tapi belum absen
- Kirim notifikasi "Jangan lupa absen dinas hari ini"

**Hapus Foto Tua:**
- Cron: tiap minggu pukul 03:00
- Hapus foto attendance >90 hari dari `storage/app/attendance/`
- Log jumlah file dihapus

### Detail Teknis

**File baru:**
```
app/Console/Kernel.php       — sudah ada, tambah schedule
app/Console/Commands/CalculateMerit.php
app/Console/Commands/PurgeOldPhotos.php   — sudah ada
app/Notifications/*          — batch notification class
```

**Scheduler (`routes/console.php`):**
```php
Schedule::command('merit:calculate')->monthlyOn(1, '00:05');
Schedule::command('merit:remind-kpi')->dailyAt('09:00');
Schedule::command('attendance:remind')->twiceDaily(8, 12);
Schedule::command('attendance:purge-photos')->weekly()->sundays()->at('03:00');
```

### Test Plan
- Command merit calculate berjalan tanpa error
- Purge hanya hapus file >90 hari
- Reminder hanya kirim ke pihak yang tepat
- Scheduler tidak bertabrakan dengan transaksi user

---

## Sprint 3+: Fitur Lanjutan (dalam antrean)

### 3. Dashboard Lanjutan ✅

Widget sebelumnya basic count-only. Sekarang sudah ada insight.

**HR Dashboard:**
- ✅ Daftar pegawai sedang dinas + status absensi real-time (`HrActiveTripsTable`)
- ✅ Grafik absensi per bulan (`HrAttendanceChart`)
- ✅ Rata-rata score merit per unit (`HrMeritPerUnitTable`)
- ✅ Alert: pegawai dengan attendance drop (`HrAttendanceDropAlert`)

**Manager Dashboard:**
- ✅ Ringkasan merit tim (avg score, distribusi) (`ManagerTeamMeritChart`)
- ✅ Timeline approval pending (mentoring) (`ManagerPendingApprovalsTable`)
- ✅ Anggota tim yang belum isi KPI (`ManagerIncompleteKpiTable`)
- ✅ Status dinas anggota tim hari ini (`ManagerTeamTripTable`)

**Employee Dashboard:**
- ✅ Progress KPI (real-time vs target) (`EmployeeKpiProgressChart`)
- ✅ Riwayat training + mentoring (`EmployeeTrainingMentoringTable`)
- ✅ Jadwal dinas hari ini + tombol absen cepat (`EmployeeActiveTripsTable`)
- ✅ Ringkasan merit terakhir (`EmployeeLatestMerit`)

### 4. PWA / Offline-First ✅

IndexedDB udah ada buat queue absen. Sekarang diperluas.

- ✅ Service worker: cache semua halaman `pegawai/*` (Cache First strategy)
- ✅ Background sync: kirim queue absen via SW `sync` event tanpa perlu buka halaman
- ✅ Installable: manifest.json + icon SVG 192/512
- ⏳ Push notifications via Web Push API (belum dikerjakan)

### 5. Enhanced Reports ✅

CSV export masih basic tanpa formatting.

- ✅ PDF report (dompdf) untuk laporan siap cetak
- ✅ Scheduled email report periodik ke HR
- ✅ Report builder di panel: pilih kolom, filter, group by
- ✅ Export format: CSV, PDF, XLSX
- ✅ Column selection (checklist kolom yang ditampilkan)
- ✅ Group by unit/jabatan
- ✅ XLSX export via openspout

### 6. Face Verification ✅

Foto absen bisa diverifikasi otomatis.

- ✅ Simpan face encoding saat absen pertama
- ✅ Bandingkan foto baru vs encoding (euclidean distance threshold 0.6)
- ✅ Flag mismatch → NeedsReview
- ✅ Library: @vladmandic/face-api (client-side, tiny model)
- ✅ Model files di public/models/ (cached oleh SW)

### 7. Multi-level Approval Workflow

Approval linear Manager→HR saat ini kaku.

- Generic workflow engine (status transition + guard)
- Configurable approval chain per module (di admin panel)
- Delegation: manager cuti → deputy approve
- Escalation: pending > 3 hari → naik ke atasan di atasnya

### 8. Notifikasi Multi-channel

Perluas dari email ke kanal lain.

- Email (Laravel Mail + queue)
- In-app (Filament database notifications)
- WhatsApp (wa.blast / API pihak ketiga)
- Telegram bot
- Channel preference per user

---

## Matriks Prioritas

| Fitur | Value | Effort | Priority |
|-------|-------|--------|----------|
| Notifikasi | High | Medium | **Sprint 1** |
| Scheduler | High | Medium | **Sprint 2** |
| Dashboard | Medium | High | Sprint 3 |
| PWA | High | High | Sprint 4 |
| Face Verify | Medium | High | ✅ Done |
| Enhanced Reports | Medium | Medium | ✅ Done |
| Multi-approval | Medium | High | Sprint 7 |
| Multi-channel notif | Low | Medium | Backlog |

---

## Catatan Teknis

- Semua scheduler menggunakan `->withoutOverlapping()` untuk cegah tumpuk
- Notifikasi email wajib queue job agar tidak blocking request
- PWA hanya untuk panel pegawai (mobile-first)
- Face verification hanya sebagai flag tambahan, bukan pengganti HR review
- Multi-approval requiring migration status jadi JSON atau parent-child approval table

---

## Test Coverage Target

- Sprint 1: 65+ test (+7 notifikasi)
- Sprint 2: 72+ test (+7 scheduler)
- Sprint 3: 80+ test
- Final: 100+ test
