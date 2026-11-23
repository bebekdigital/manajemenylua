<?php

use App\Models\UserActivityLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('activity-logs:prune', function () {
    $deleted = UserActivityLog::pruneExpiredLogs(force: true);
    $this->info("Menghapus {$deleted} log aktivitas yang lebih dari ".UserActivityLog::RETENTION_DAYS.' hari.');
})->purpose('Hapus otomatis log aktivitas user yang lebih dari 7 hari');

Schedule::command('activity-logs:prune')->dailyAt('02:00');
