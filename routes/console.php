<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:aggregate')
    ->dailyAt('23:59')
    ->timezone('Asia/Jakarta');

Schedule::command('career:expire-promotions')
    ->dailyAt('00:05')
    ->timezone('Asia/Jakarta');

Schedule::command('career:scan-candidates')
    ->monthlyOn(1, '00:10')
    ->timezone('Asia/Jakarta');
