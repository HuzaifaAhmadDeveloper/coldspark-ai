<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Drives fully-automatic campaign sending: every minute, find campaign emails
// whose scheduled_at has arrived and queue them via SendCampaignEmailJob.
//
// Deliberately NOT ->runInBackground(): that detaches this command into a
// separate untracked process, which is only useful to stop a slow command
// from blocking *other* scheduled commands in the same minute. There are
// none here, dispatch-due is fast (a bounded query + bulk update, no actual
// sending happens inline), and on Windows the detached spawn was observed to
// let schedule:run return before dispatch-due's queue inserts had actually
// landed — which then raced against windows/run-campaign-worker.bat's very
// next line (queue:work --stop-when-empty), which would find nothing and
// exit clean. Running inline makes the two steps fully sequential and safe.
Schedule::command('campaigns:dispatch-due')
    ->everyMinute()
    ->withoutOverlapping();
