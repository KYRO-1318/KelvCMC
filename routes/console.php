<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduler shortcuts (main schedule lives in bootstrap/app.php)
|--------------------------------------------------------------------------
*/
Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();
