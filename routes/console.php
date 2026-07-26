<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('attendance:aggregate')
    ->dailyAt('23:59')
    ->timezone('Asia/Jakarta');

Schedule::command('career:expire-promotions')
    ->dailyAt('00:15')
    ->timezone('Asia/Jakarta');

Schedule::command('career:scan-candidates')
    ->monthlyOn(1, '00:30')
    ->timezone('Asia/Jakarta');
