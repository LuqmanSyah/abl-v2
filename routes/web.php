<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app')->name('home');
Route::redirect('/login', '/app/login')->name('login');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/app/dinas/{dutyTrip}/absensi', [AttendanceController::class, 'show'])
        ->name('attendance.capture');
    Route::post('/app/dinas/{dutyTrip}/absensi', [AttendanceController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('attendance.store');
    Route::get('/absensi/{attendance}/foto', [AttendanceController::class, 'photo'])
        ->name('attendance.photo');
});
