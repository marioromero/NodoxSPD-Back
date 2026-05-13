<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompleteOnboardingRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OnboardingController extends Controller
{
    use ApiResponse;

    public function complete(CompleteOnboardingRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $company = $user->company;

            if ($company->hasCompletedOnboarding()) {
                return $this->error('El onboarding ya ha sido completado previamente.', null, 409);
            }

            DB::transaction(function () use ($company, $request) {

                // Si no es extranjera, forzamos el contacto local a null por seguridad de datos
                $localContact = $request->is_foreign_entity ? $request->local_contact_for_foreign_entity : null;

                $company->update([
                    'tax_id' => $request->tax_id,
                    'legal_address' => $request->legal_address,
                    'arco_contact_email' => $request->arco_contact_email,
                    'legal_representative_name' => $request->legal_representative_name,
                    'legal_representative_tax_id' => $request->legal_representative_tax_id,
                    'is_foreign_entity' => $request->is_foreign_entity,
                    'local_contact_for_foreign_entity' => $localContact,
                    'dpo_contact' => $request->dpo_contact,
                    'onboarding_completed_at' => now(),
                ]);

                // Sincronizar sectores en la tabla pivote
                $company->sectors()->sync($request->sector_ids);
            });

            // Recargamos incluyendo la relación pivote para el frontend
            $user->load(['company.sectors']);

            return $this->success('Configuración legal completada exitosamente.', $user);

        } catch (\Exception $e) {
            Log::error('Error en onboarding', [
                'company_id' => $company->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            return $this->error('Ocurrió un error al procesar el onboarding.', null, 500);
        }
    }
}
