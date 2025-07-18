<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic payout generation to run daily
Schedule::command('payouts:generate')
    ->daily()
    ->at('09:00')
    ->withoutOverlapping()
    ->runInBackground();
