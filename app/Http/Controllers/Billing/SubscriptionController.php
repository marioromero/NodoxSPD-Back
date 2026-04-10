<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->with([
                'prices' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('billing_cycle')
                        ->orderBy('amount');
                },
                'features',
            ])
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $plans,
        ]);
    }
}
