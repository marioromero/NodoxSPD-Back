<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consent_logs', function (Blueprint $table) {
            // Llave primaria y identificadores del visitante
            $table->bigIncrements('id');
            $table->uuid('visitor_uuid')->index();
            $table->string('identifier')->nullable()->index();

            // Relaciones: empresa y política vigente al momento del consentimiento
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('company_policy_id')->constrained('company_policies')->restrictOnDelete();

            // Datos del consentimiento: propósitos aceptados/rechazados y hashes de integridad
            $table->json('purposes');
            $table->string('policy_hash', 64)->index();
            $table->string('proof_hash', 64)->unique();

            // Timestamps de auditoría: cuándo ocurrió vs cuándo se registró en el sistema
            $table->timestamp('consent_occurred_at')->useCurrent();
            $table->timestamp('recorded_at')->useCurrent();

            // Método de captura y metadatos del contexto
            $table->enum('capture_method', ['live_widget', 'live_portal', 'bulk_import'])->index();
            $table->string('capture_notes')->nullable();

            // Trazabilidad de importación masiva (nullable para capturas live)
            $table->string('import_row_hash', 64)->nullable()->unique();
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();

            // Huellas de seguridad (hashes, no valores crudos) para auditoría forense
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('context_fingerprint', 64)->nullable();

            // Solo created_at (sin updated_at): el registro es inmutable
            $table->timestamp('created_at')->useCurrent();

            // Índices compuestos para queries de reporting y auditoría
            $table->index(['company_id', 'capture_method']);
            $table->index(['company_id', 'consent_occurred_at']);
            $table->index(['company_policy_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consent_logs');
    }
};
