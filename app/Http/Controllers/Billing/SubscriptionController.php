<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\PlanPrice;
use App\Services\Billing\PaymentFactory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    use ApiResponse;

    /**
     * Obtiene la suscripción actual de la empresa.
     */
    public function current(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        if (! $company) {
            return $this->error('Acceso denegado. Solo las empresas tienen suscripciones.', null, 403);
        }

        // Traemos la última suscripción con los datos del plan
        $subscription = $company->subscriptions()
            ->with('planPrice.plan')
            ->latest()
            ->first();

        return $this->success('Estado de la suscripción', $subscription);
    }

    /**
     * Inicia el proceso de compra o cambio de plan.
     */
    public function store(Request $request): JsonResponse
    {
        // Esta validación idealmente irá en un FormRequest más adelante
        $request->validate([
            'plan_price_id' => ['required', 'exists:plan_prices,id'],
            'payment_method' => ['required', 'string'], // ej: 'bank_transfer', 'stripe'
        ]);

        $company = $request->user()->company;

        if (! $company) {
            return $this->error('Acceso denegado. Solo las empresas pueden suscribirse.', null, 403);
        }

        $price = PlanPrice::findOrFail($request->plan_price_id);

        try {
            // Transacción de Base de Datos para asegurar la integridad contable
            $result = DB::transaction(function () use ($company, $price, $request) {

                // Instanciamos el motor de pago según lo que eligió el usuario
                $gateway = PaymentFactory::make($request->payment_method);

                // Procesamos el pago (devuelve instrucciones o URL de pasarela)
                return $gateway->process($company, $price);
            });

            return $this->success('Proceso de pago iniciado con éxito', $result);

        } catch (\Exception $e) {
            // Logueamos el error real internamente, pero le devolvemos un mensaje limpio a Angular
            // Log::error('Error en pago: ' . $e->getMessage());
            return $this->error('No se pudo procesar el pago: '.$e->getMessage(), null, 500);
        }
    }
}
