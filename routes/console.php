<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schedule;

//backup database daily at 1 AM
if (App::environment('production')) {
    Schedule::command('backup:run --only-db')
        ->dailyAt('01:00');

    Schedule::command('backup:cleanup')
        ->dailyAt('02:00');
}
