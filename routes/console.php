<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('crawler:run --limit=5')
    ->dailyAt('16:40')
    ->timezone('Asia/Karachi')
    ->appendOutputTo(storage_path('logs/crawler-schedule.log'));
