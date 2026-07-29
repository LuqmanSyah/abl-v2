1. `yagni:` CRUD `DutyLocation` menduplikasi input lokasi langsung. Simpan snapshot lokasi langsung pada `DutyTrip`. [app/Filament/Resources/DutyLocations, app/Models/DutyLocation.php]
2. `native:` kamera, canvas, retake, watermark masih terlalu besar. Pakai `<input type="file" accept="image/*" capture="environment">`. [resources/views/attendance/capture.blade.php]
3. `yagni:` `AttendanceNeedsReview` menduplikasi monitoring status HR. Gunakan filter `NeedsReview` dan `HrAttendanceStats`. [app/Notifications/AttendanceNeedsReview.php, app/Services/AttendanceRecorder.php]
4. `yagni:` preference notifikasi + `HasDynamicChannels` tersisa untuk sedikit notification. Pakai channel tetap `database`/`mail`. [app/Models/Concerns/HasDynamicChannels.php, app/Filament/Pages/EditProfile.php, app/Filament/Resources/Users/Schemas/UserForm.php]
5. `delete:` `HrAttendanceDropAlert` berisi analitik spekulatif dan mengulang statistik absensi. Pertahankan `HrAttendanceStats`. [app/Filament/Widgets/HrAttendanceDropAlert.php]
6. `shrink:` aksi verifikasi hadir di empat tempat. Pertahankan hanya Attendance table. [app/Filament/Resources/Attendances/Tables/AttendancesTable.php, app/Filament/Resources/Attendances/Pages/ViewAttendance.php, app/Filament/Resources/DutyTrips]
7. `shrink:` `Pending`, `Rejected`, dan `Completed` tidak punya transition nyata. Pertahankan `Approved`/`Cancelled`; turunkan selesai dari `ends_at`. [app/Enums/DutyTripStatus.php, app/Models/DutyTrip.php]
8. `shrink:` migration daily attendance masih migration tambahan pada project disposable. Gabungkan `attendance_date`, `review_reason`, dan unique harian ke migration awal. [database/migrations/2026_07_23_000000_support_daily_duty_attendances.php]
9. `delete:` `mock_location_suspected` selalu dikirim `0`, lalu dipakai sebagai boolean turunan generik. `NeedsReview` dan `review_reason` sudah cukup. [resources/views/attendance/capture.blade.php, app/Services/AttendanceRecorder.php, app/Models/Attendance.php]
10. `delete:` upload dokumen dinas hanya menampilkan path file tanpa workflow konsumsi. Tidak ada pengganti. [app/Filament/Resources/DutyTrips/Schemas/DutyTripForm.php, app/Filament/Resources/DutyTrips/Schemas/DutyTripInfolist.php]
11. `delete:` `getRelations()` kosong mengulang default Filament. Tidak ada pengganti. [app/Filament/Resources/Attendances/AttendanceResource.php, app/Filament/Resources/DutyTrips/DutyTripResource.php, app/Filament/Resources/DutyLocations/DutyLocationResource.php]
12. `shrink:` controller eager-load `attendances`, tetapi Blade tetap menjalankan query relation baru. Load hanya `employee`. [app/Http/Controllers/AttendanceController.php]

`net: -850 lines, -0 deps possible.`
