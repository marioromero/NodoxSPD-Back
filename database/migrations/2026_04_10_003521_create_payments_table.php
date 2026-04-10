<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // nullOnDelete(): Si la suscripción se borra, no borramos el registro financiero, solo lo desvinculamos
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();

            $table->integer('amount_paid'); // En centavos
            $table->string('currency', 3);
            $table->string('payment_method')->nullable(); // Ej: 'credit_card', 'transfer'
            $table->string('transaction_reference')->nullable(); // ID de pasarela (Stripe, etc)

            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('invoice_url')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
