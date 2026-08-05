<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Drives fully-automatic campaign sending: every minute, find campaign emails
// whose scheduled_at has arrived and queue them via SendCampaignEmailJob.
Schedule::command('campaigns:dispatch-due')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
