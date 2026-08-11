<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 |--------------------------------------------------------------------------
 | Cronjob: Purga de pending_consents
 |--------------------------------------------------------------------------
 |
 | Marca como expired los tokens vencidos, y elimina fisicamente los
 | registros expirados con >24h de gracia y los confirmados con >7 dias.
 | El ledger inmutable (consent_logs) NUNCA se toca.
 |
 */
Schedule::call(function (): void {
    DB::table('pending_consents')
        ->where('status', 'pending')
        ->where('expires_at', '<', now())
        ->update(['status' => 'expired']);

    DB::table('pending_consents')
        ->where('status', 'expired')
        ->where('expires_at', '<', now()->subHours(24))
        ->delete();

    DB::table('pending_consents')
        ->where('status', 'confirmed')
        ->whereNotNull('confirmed_at')
        ->where('confirmed_at', '<', now()->subDays(7))
        ->delete();
})->hourly()
    ->name('purge-pending-consents')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Cronjob: Poda de job_batches (Laravel nativo)
|--------------------------------------------------------------------------
|
| Elimina lotes completados hace >48h y lotes sin finalizar hace >72h.
| Previene crecimiento descontrolado de la tabla job_batches.
| Los registros de company_batches quedan huérfanos (nullOnDelete) para
| preservar la pista de auditoría.
|
*/
Schedule::command('queue:prune-batches --hours=48')->daily();
Schedule::command('queue:prune-batches --hours=48 --unfinished=72')->daily();
