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
        Schema::create('pending_consents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('company_policy_id')->constrained('company_policies')->restrictOnDelete();
            $table->string('token', 64)->unique();
            $table->json('pii_payload');
            $table->string('pii_hash', 64)->index();
            $table->enum('status', ['pending', 'confirmed', 'expired'])->default('pending')->index();
            $table->enum('source', ['zapier', 'make', 'manual_panel', 'webhook_generic'])->index();
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->string('pending_uniqueness_key', 200)->storedAs("IF(status = 'pending', CONCAT(pii_hash, ':', company_policy_id), NULL)")->nullable();
            $table->timestamps();

            $table->index(['expires_at', 'status']);
            $table->index(['company_id', 'status']);
        });

        DB::statement('ALTER TABLE pending_consents ADD UNIQUE KEY uq_pending_active (pending_uniqueness_key)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_consents');
    }
};
