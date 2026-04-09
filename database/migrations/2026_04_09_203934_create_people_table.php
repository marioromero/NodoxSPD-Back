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
        Schema::create('people', function (Blueprint $table) {
$table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Relación recursiva para Tutor Legal (menores de 14 años)
            $table->foreignId('guardian_id')->nullable()->constrained('personas')->nullOnDelete();

            // Datos de Identidad
            $table->string('first_name');
            $table->string('last_name');
            $table->string('tax_id')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();

            // Gestión de Derechos y Datos
            $table->json('consent_logs')->nullable();
            $table->json('sensitive_data_categories')->nullable();
            $table->boolean('is_blocked')->default(false);

            // Tiempos legales y notificaciones
            $table->timestamp('retention_expiry_at')->nullable();
            $table->timestamp('last_notified_breach_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
