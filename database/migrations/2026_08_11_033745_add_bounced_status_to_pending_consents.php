<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Añade el estado 'bounced' al enum status de pending_consents.
 *
 * El webhook de Resend marca los correos rebotados para que la Pyme
 * pueda reintentar o dar de baja el destinatario.
 *
 * SQLite (tests): la migración original 2026_06_26_000005 ya incluye
 * 'bounced' en el enum, por lo que esta migración es no-op en SQLite.
 * MySQL/MariaDB (producción): ALTER TABLE MODIFY COLUMN para añadir
 * el valor al enum existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // No-op: la migración original ya incluye 'bounced' en el enum.
            return;
        }

        DB::statement("ALTER TABLE pending_consents MODIFY COLUMN status ENUM('pending','confirmed','expired','bounced') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE pending_consents MODIFY COLUMN status ENUM('pending','confirmed','expired') NOT NULL DEFAULT 'pending'");
    }
};
