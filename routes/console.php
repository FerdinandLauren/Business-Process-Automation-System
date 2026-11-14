<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Due-soon reminders and SLA breach records for pending approval tasks.
Schedule::command('bpa:sla-monitor')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();
