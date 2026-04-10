<?php

namespace App\Services\Billing\Gateways;

use App\Models\Company;
use App\Models\PlanPrice;

interface PaymentGatewayInterface
{
    /**
     * Procesa la intención de pago inicial
     */
    public function process(Company $company, PlanPrice $price): array;

    /**
     * Verifica si el pago fue exitoso (Webhook para Stripe, o Manual para Transferencia)
     */
    public function verify(string $transactionReference): bool;
}
