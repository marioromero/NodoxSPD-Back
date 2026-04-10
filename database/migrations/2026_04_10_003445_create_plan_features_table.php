<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();

            $table->string('feature_code'); // Ej: 'max_users'
            $table->string('feature_value'); // Ej: '10' o 'true'

            $table->timestamps();

            // Un plan no debería tener dos veces la misma característica repetida
            $table->unique(['plan_id', 'feature_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
