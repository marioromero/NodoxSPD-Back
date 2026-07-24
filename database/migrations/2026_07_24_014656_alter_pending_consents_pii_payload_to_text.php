<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cambia pii_payload de json a text en pending_consents.
 *
 * El mutador del modelo PendingConsent cifra el payload con Crypt::encryptString,
 * cuyo resultado es un string base64 que no es JSON válido. MariaDB rechaza el
 * insert en columnas json con CHECK constraint. Text permite almacenar el cifrado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_consents', function (Blueprint $table) {
            $table->text('pii_payload')->change();
        });
    }

    public function down(): void
    {
        Schema::table('pending_consents', function (Blueprint $table) {
            $table->json('pii_payload')->change();
        });
    }
};
