<?php

use Illuminate\Support\Facades\Schedule;

// Agrégation horaire des impressions/clics vers stats_daily
Schedule::command('stats:aggregate')->hourly()->withoutOverlapping();

// Déblocage quotidien des gains éditeurs arrivés à maturité
Schedule::command('earnings:release')->dailyAt('02:00')->withoutOverlapping();
