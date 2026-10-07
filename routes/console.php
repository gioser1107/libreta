<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
 * Tasas BCV: a última hora el sitio suele reflejar lo que aplica desde el día siguiente.
 * Se guarda en bcv_rates con date_effective = mañana (Caracas), no el día del run.
 * Manual: php artisan bcv:sync-official (hoy) o --date=2026-10-06
 */
Schedule::call(function (): void {
    $effectiveDate = now('America/Caracas')->addDay()->toDateString();
    $code = Artisan::call('bcv:sync-official', ['--date' => $effectiveDate]);
    if ($code !== 0) {
        throw new RuntimeException('bcv:sync-official no guardó EUR y USD para '.$effectiveDate);
    }
})->dailyAt('20:15')->timezone('America/Caracas');
