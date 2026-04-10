<?php

namespace App\Services\Billing;

use App\Services\Billing\Gateways\BankTransferGateway;
//use App\Services\Billing\Gateways\StripeGateway; descomentar
use Exception;

class PaymentFactory
{
    public static function make(string $method)
    {
        return match ($method) {
            'bank_transfer' => new BankTransferGateway(),
            //'stripe' => new StripeGateway(),
            // 'mercadopago' => new MercadoPagoGateway(), descomentar
            default => throw new Exception("Método de pago no soportado"),
        };
    }
}
