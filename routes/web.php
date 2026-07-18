<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\HrReportController;
use App\Http\Controllers\WebPushController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('home');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('login.store');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/pegawai/dinas/{dutyTrip}/absensi', [AttendanceController::class, 'show'])
        ->name('attendance.capture');
    Route::post('/pegawai/dinas/{dutyTrip}/absensi', [AttendanceController::class, 'store'])
        ->name('attendance.store');
    Route::get('/absensi/{attendance}/foto', [AttendanceController::class, 'photo'])
        ->name('attendance.photo');
    Route::get('/hr/laporan', [HrReportController::class, 'index'])->name('hr.reports.index');
    Route::get('/hr/laporan/ekspor', [HrReportController::class, 'export'])->name('hr.reports.export');
    Route::get('/hr/laporan/pdf', [HrReportController::class, 'exportPdf'])->name('hr.reports.pdf');
    Route::get('/hr/laporan/xlsx', [HrReportController::class, 'exportXlsx'])->name('hr.reports.xlsx');

    Route::post('/webpush/subscribe', [WebPushController::class, 'subscribe'])->name('webpush.subscribe');
    Route::post('/webpush/unsubscribe', [WebPushController::class, 'unsubscribe'])->name('webpush.unsubscribe');
});
