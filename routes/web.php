<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\HrReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/pegawai/dinas/{dutyTrip}/absensi', [AttendanceController::class, 'show'])
        ->name('attendance.capture');
    Route::post('/pegawai/dinas/{dutyTrip}/absensi', [AttendanceController::class, 'store'])
        ->name('attendance.store');
    Route::get('/absensi/{attendance}/foto', [AttendanceController::class, 'photo'])
        ->name('attendance.photo');
    Route::get('/hr/laporan', [HrReportController::class, 'index'])->name('hr.reports.index');
    Route::get('/hr/laporan/ekspor', [HrReportController::class, 'export'])->name('hr.reports.export');
});
