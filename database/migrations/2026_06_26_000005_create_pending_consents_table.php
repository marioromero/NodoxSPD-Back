<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        Schema::create('pending_consents', function (Blueprint $table) use ($driver) {
            // Llave primaria y relaciones
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('company_policy_id')->constrained('company_policies')->restrictOnDelete();

            // Token único de confirmación y payload cifrado con PII del visitante
            $table->string('token', 64)->unique();
            $table->json('pii_payload');
            $table->string('pii_hash', 64)->index();

            // Estado del consentimiento pendiente y origen de la captura
            $table->enum('status', ['pending', 'confirmed', 'expired'])->default('pending')->index();
            $table->enum('source', ['zapier', 'make', 'manual_panel', 'webhook_generic'])->index();

            // Control de expiración del token y confirmación
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();

            // Columna generada (stored): solo tiene valor cuando status='pending'.
            // Permite un índice único parcial: solo un consentimiento pendiente activo
            // por combinación pii_hash + company_policy_id (evita duplicados pendientes).
            $expression = $driver === 'sqlite'
                ? "CASE WHEN status = 'pending' THEN pii_hash || ':' || company_policy_id ELSE NULL END"
                : "IF(status = 'pending', CONCAT(pii_hash, ':', company_policy_id), NULL)";

            $table->string('pending_uniqueness_key', 200)->storedAs($expression)->nullable();
            $table->timestamps();

            // Índices compuestos para limpieza de expirados y reporting por empresa
            $table->index(['expires_at', 'status']);
            $table->index(['company_id', 'status']);

            // SQLite: el unique se declara inline porque no soporta ALTER TABLE ADD UNIQUE KEY
            if ($driver === 'sqlite') {
                $table->unique('pending_uniqueness_key', 'uq_pending_active');
            }
        });

        // MySQL/MariaDB: el unique se agrega via ALTER TABLE para nombrar el índice
        if ($driver !== 'sqlite') {
            DB::statement('ALTER TABLE pending_consents ADD UNIQUE KEY uq_pending_active (pending_uniqueness_key)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_consents');
    }
};
