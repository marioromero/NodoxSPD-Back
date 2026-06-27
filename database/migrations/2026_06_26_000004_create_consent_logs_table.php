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
            $table->bigIncrements('id');
            $table->uuid('visitor_uuid')->index();
            $table->string('identifier')->nullable()->index();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('company_policy_id')->constrained('company_policies')->restrictOnDelete();
            $table->json('purposes');
            $table->string('policy_hash', 64)->index();
            $table->string('proof_hash', 64)->unique();
            $table->timestamp('consent_occurred_at')->useCurrent();
            $table->timestamp('recorded_at')->useCurrent();
            $table->enum('capture_method', ['live_widget', 'live_portal', 'bulk_import'])->index();
            $table->string('capture_notes')->nullable();
            $table->string('import_row_hash', 64)->nullable()->unique();
            $table->foreignId('import_batch_id')->nullable()->constrained('import_batches')->nullOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('context_fingerprint', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();

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
