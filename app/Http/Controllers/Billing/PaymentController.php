<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    /**
     * Lista el historial de transacciones/pagos de la empresa.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        if (!$company) {
            return $this->error('Acceso denegado.', null, 403);
        }

        // Obtenemos los pagos ordenados del más reciente al más antiguo,
        // cargando la información del plan para mostrarla en la factura.
        $payments = $company->payments()
            ->with('subscription.planPrice.plan')
            ->latest()
            ->get();

        return $this->success('Historial de pagos', $payments);
    }
}
