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
