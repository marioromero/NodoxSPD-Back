<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        // Plan 1: Starter (Mensual)
        $starter = Plan::firstOrCreate(
            ['slug' => 'starter-plan'],
            [
                'name' => 'Plan Starter',
                'description' => 'Ideal para empresas pequeñas que necesitan cumplimiento básico.',
                'is_active' => true,
            ]
        );

        // Precios del Plan Starter
        $starter->prices()->firstOrCreate([
            'billing_cycle' => 'monthly',
            'amount' => 2900, // $29.00 USD
            'currency' => 'USD',
        ]);

        // Características del Plan Starter
        $starter->features()->firstOrCreate(['feature_code' => 'max_dpo', 'feature_value' => '1']);
        $starter->features()->firstOrCreate(['feature_code' => 'custom_legal_docs', 'feature_value' => 'false']);

        // Plan 2: Enterprise (Anual)
        $enterprise = Plan::firstOrCreate(
            ['slug' => 'enterprise-plan'],
            [
                'name' => 'Plan Enterprise',
                'description' => 'Cumplimiento total con múltiples DPOs y documentos personalizados.',
                'is_active' => true,
            ]
        );

        $enterprise->prices()->firstOrCreate([
            'billing_cycle' => 'yearly',
            'amount' => 29900, // $299.00 USD
            'currency' => 'USD',
        ]);

        $enterprise->features()->firstOrCreate(['feature_code' => 'max_dpo', 'feature_value' => 'unlimited']);
        $enterprise->features()->firstOrCreate(['feature_code' => 'custom_legal_docs', 'feature_value' => 'true']);
    }
}
