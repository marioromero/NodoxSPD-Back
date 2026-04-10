<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // restrictOnDelete(): Evita que borres por error un precio que tiene suscripciones activas
            $table->foreignId('plan_price_id')->constrained('plan_prices')->restrictOnDelete();

            $table->enum('status', ['trialing', 'active', 'past_due', 'canceled', 'unpaid'])->default('active');

            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable(); // Nullable si es lifetime
            $table->timestamp('canceled_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
