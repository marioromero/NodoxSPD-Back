<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    use ApiResponse;

    /**
     * Devuelve todos los planes activos con sus precios y características.
     */
    public function index(): JsonResponse
    {
        // Eager loading para evitar el problema de N+1 consultas.
        // Solo traemos los precios que estén activos.
        $plans = Plan::with([
            'prices' => fn($query) => $query->where('is_active', true),
            'features'
        ])
        ->where('is_active', true)
        ->get();

        return $this->success('Catálogo de planes', $plans);
    }
}
