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
        Schema::create('companies', function (Blueprint $table) {
$table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->uuid('public_uuid')->unique();

            // Datos del Negocio
            $table->string('business_name');
            $table->string('tax_id')->nullable()->unique();
            $table->string('industry_type')->nullable();
            $table->string('legal_address')->nullable();
            $table->boolean('is_foreign_entity')->default(false);

            // Cumplimiento y Legal
            $table->string('dpo_designation_act')->nullable();
            $table->json('dpo_contact')->nullable();
            $table->json('legal_settings')->nullable();
            $table->timestamp('last_impact_assessment_at')->nullable();
            $table->integer('security_policy_version')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
