<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('sync:beds')->everyFiveMinutes()->withoutOverlapping();

Artisan::command('visit:reset', function () {
    \App\Models\Equipment::where('visit_dpjp', 'Sudah')->update(['visit_dpjp' => 'Belum']);
    $this->info('DPJP visit status reset successfully.');
})->purpose('Reset daily DPJP visit checklist');

Schedule::command('visit:reset')->dailyAt('00:00');
