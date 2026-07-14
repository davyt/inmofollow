<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('whatsapp:send-scheduled')->everyMinute()->withoutOverlapping();
Schedule::command('notify:follow-ups')->dailyAt('08:00');
Schedule::command('leads:classify-no-response')->everyTwoHours()->withoutOverlapping();
// Fallback: clasifica con IA los leads que tienen mensajes entrantes pero sin clasificación
// (cubre casos donde el agente no emitió el tag, bots sin patrón conocido, etc.)
Schedule::command('leads:classify-responses')->dailyAt('03:00')->withoutOverlapping();
