<?php

namespace App\Services\Billing\Gateways;

use App\Models\Company;
use App\Models\Payment;
use App\Models\PlanPrice;
use App\Models\Subscription;
use Illuminate\Support\Str;

class BankTransferGateway implements PaymentGatewayInterface
{
    public function process(Company $company, PlanPrice $price): array
    {
        // 1. Creamos la suscripción en estado 'unpaid'
        $subscription = Subscription::create([
            'company_id' => $company->id,
            'plan_price_id' => $price->id,
            'status' => 'unpaid',
            'starts_at' => now(),
        ]);

        // 2. Registramos el pago como pendiente
        $reference = 'TRX-'.strtoupper(Str::random(10));

        Payment::create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'amount_paid' => $price->amount,
            'currency' => $price->currency,
            'payment_method' => 'bank_transfer',
            'transaction_reference' => $reference,
            'status' => 'pending',
        ]);

        // 3. Devolvemos las instrucciones al frontend
        return [
            'status' => 'pending_action',
            'message' => 'Por favor, transfiere el monto a la cuenta XXXXX.',
            'reference' => $reference,
            'amount' => $price->amount,
        ];
    }

    public function verify(string $transactionReference): bool
    {
        // Esta lógica la usaría tu panel de SuperAdmin cuando tú
        // verifiques en tu banco que el dinero llegó, para cambiar
        // el estado a 'completed' y activar la suscripción.
        return true;
    }
}
