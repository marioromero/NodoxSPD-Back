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
        Schema::create('import_batches', function (Blueprint $table) {
            // Llave primaria y relaciones
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('company_policy_id')->constrained('company_policies')->restrictOnDelete();

            // Ruta del archivo importado y estado del procesamiento del lote
            $table->string('file_path');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'paused'])->default('pending')->index();

            // Contadores de progreso del lote
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('last_processed_row')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);

            // Errores de validación por fila y timestamp de finalización
            $table->json('validation_errors')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Índice compuesto para consultar lotes por empresa y estado
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
