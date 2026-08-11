<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla puente company_batches: relaciona empresas con lotes de Bus::batch.
 *
 * Permite aplicar aislamiento multitenant: cada empresa solo puede consultar
 * el progreso de sus propios lotes. Validado en BatchProgressController.
 *
 * La FK a job_batches usa string id (UUID generado por Laravel Bus::batch).
 * Si se elimina un lote, el registro puente queda huérfano (no cascade) para
 * preservar la pista de auditoría.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        Schema::create('company_batches', function (Blueprint $table) use ($driver) {
            $table->bigIncrements('id');

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->string('batch_id');

            $table->timestamps();

            // SQLite (tests): FK a job_batches omitida porque Bus::fake()
            // no inserta el batch real. MySQL (producción): FK con nullOnDelete.
            if ($driver !== 'sqlite') {
                $table->foreign('batch_id', 'fk_company_batches_job_batches')
                    ->references('id')
                    ->on('job_batches')
                    ->nullOnDelete();
            }

            $table->index(['company_id', 'created_at']);
            $table->unique(['company_id', 'batch_id'], 'uq_company_batch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_batches');
    }
};
