<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule monthly billing notification (every 1st of month at 09:00 WIB)
Schedule::command('billing:send-monthly-notification')
    ->monthlyOn(1, '09:00')
    ->timezone('Asia/Jakarta');

// Schedule daily low meter check (every day at 08:00 WIB)
Schedule::command('meters:check-low')
    ->daily()
    ->at('08:00')
    ->timezone('Asia/Jakarta');
