<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();

            $table->string('currency', 3)->default('USD');
            $table->integer('amount'); // Siempre en centavos (ej. 1500 = $15.00)
            $table->enum('billing_cycle', ['monthly', 'yearly', 'lifetime']);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_prices');
    }
};
