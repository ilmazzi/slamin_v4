<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notifications:send-likes-summary')
    ->dailyAt('18:00')
    ->timezone('Europe/Rome')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('notifications:send-weekly-digest')
    ->weeklyOn(1, '09:00')
    ->timezone('Europe/Rome')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('backup:clean')
    ->dailyAt('01:00')
    ->timezone('Europe/Rome')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('backup:run')
    ->dailyAt('01:20')
    ->timezone('Europe/Rome')
    ->withoutOverlapping(180)
    ->onOneServer();

Schedule::command('backup:monitor')
    ->dailyAt('03:00')
    ->timezone('Europe/Rome')
    ->withoutOverlapping()
    ->onOneServer();
