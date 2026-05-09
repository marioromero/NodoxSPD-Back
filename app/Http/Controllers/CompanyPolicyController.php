<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyPolicyRequest;
use App\Http\Requests\UpdateCompanyPolicyRequest;
use App\Models\CompanyPolicy;
use App\Models\LegalTemplate;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

class CompanyPolicyController extends Controller
{
    use AuthorizesRequests;

    /**
     * Lista el historial de políticas de la empresa.
     */
    public function index(Request $request)
    {
        $company = $request->user()->company;
        $query = CompanyPolicy::where('company_id', $company->id);

        // Filtrar por tipo de documento si se proporciona
        if ($request->has('type')) {
            $query->where('document_type', $request->input('type'));
        }

        $policies = $query->with('template:id,name,version') // Traemos info básica de la plantilla
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $policies]);
    }

    /**
     * Guarda una nueva política (En estado Borrador).
     */
    public function store(StoreCompanyPolicyRequest $request)
    {
        $company = $request->user()->company;
        $documentType = $request->validated('document_type');
        // Para el flujo de wizard, wizard_data puede estar vacío inicialmente
        $wizardData = $request->validated('wizard_data') ?? [];

        // 1. Buscar la plantilla legal ACTIVA para este tipo de documento
        $activeTemplate = LegalTemplate::where('document_type', $documentType)
            ->where('is_active', true)
            ->firstOrFail();

        // 2. Calcular la siguiente versión para esta empresa
        $latestCompanyPolicy = CompanyPolicy::where('company_id', $company->id)
            ->where('document_type', $documentType)
            ->max('company_version');

        $nextVersion = $latestCompanyPolicy ? $latestCompanyPolicy + 1 : 1;

        // 3. Crear el registro inmutable
        $policy = CompanyPolicy::create([
            'company_id' => $company->id,
            'legal_template_id' => $activeTemplate->id,
            'document_type' => $documentType,
            'company_version' => $nextVersion,
            'wizard_data' => $wizardData,
            'status' => 'draft',
        ]);

        return response()->json([
            'message' => 'Política generada exitosamente en borrador.',
            'data' => $policy->fresh(),
        ], 201);
    }

    /**
     * EL MOTOR: Renderiza el HTML final inyectando las variables al Blade de la DB.
     */
    public function render(CompanyPolicy $policy, Request $request)
    {
        // Seguridad: Asegurar que la política pertenece a la empresa del usuario
        if ((int) $policy->company_id !== (int) $request->user()->company->id) {
            abort(403, 'Acceso no autorizado a este documento.');
        }

        // Cargamos la relación de la empresa y la plantilla
        $policy->load(['company', 'template']);

        try {
            $wizardData = $this->normalizeWizardData($policy->wizard_data ?? [], $policy->document_type);

            $htmlOutput = Blade::render($policy->template->content, [
                'company' => $policy->company,
                'policy' => $policy,
                'wizard_data' => $wizardData,
            ]);

            return response()->json([
                'status' => true,
                'data' => [
                    'html' => $htmlOutput,
                    'document_type' => $policy->document_type,
                    'version' => $policy->company_version,
                    'status' => $policy->status,
                ],
            ]);

        } catch (\Throwable $e) {
            Log::error('Error compilando documento legal', [
                'policy_id' => $policy->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Error al compilar el documento legal.',
                'data' => null,
            ], 500);
        }
    }

    /**
     * Publica la política y genera el Sello de Integridad (Hash).
     */
    public function publish(CompanyPolicy $policy, Request $request)
    {
        $this->authorize('publish', $policy);

        // Solo se pueden publicar políticas en estado draft
        if ($policy->status !== 'draft') {
            return response()->json([
                'status' => false,
                'message' => 'Solo se pueden publicar políticas en estado borrador.',
            ], 403);
        }

        if ($policy->status === 'published') {
            return response()->json(['message' => 'La política ya está publicada.'], 400);
        }

        $policy->load(['company', 'template']);

        $wizardData = $this->normalizeWizardData($policy->wizard_data ?? [], $policy->document_type);

        // 1. Renderizamos el documento final tal como quedará
        $htmlOutput = Blade::render($policy->template->content, [
            'company' => $policy->company,
            'policy' => $policy,
            'wizard_data' => $wizardData,
        ]);

        // 2. Generamos el hash SHA-256 del contenido exacto (Prueba de Inmutabilidad Legal)
        $integrityHash = hash('sha256', $htmlOutput);

        // 3. Actualizamos estado
        $policy->update([
            'status' => 'published',
            'published_at' => now(),
            'integrity_hash' => $integrityHash,
        ]);

        return response()->json([
            'message' => 'Política publicada y sellada legalmente.',
            'integrity_hash' => $integrityHash,
            'published_at' => $policy->published_at,
            'data' => $policy->fresh(),
        ]);
    }

    /**
     * Muestra el borrador de una política específica.
     */
    public function show(CompanyPolicy $policy, Request $request)
    {
        $this->authorize('view', $policy);

        // Cargar la relación con la plantilla para devolver información completa
        $policy->load('template');

        return response()->json([
            'status' => true,
            'data' => $policy->fresh(),
        ]);
    }

    /**
     * Actualiza un borrador de política.
     */
    public function update(UpdateCompanyPolicyRequest $request, CompanyPolicy $policy)
    {
        $this->authorize('update', $policy);

        // Validar que el borrador esté en estado draft
        if ($policy->status !== 'draft') {
            return response()->json([
                'status' => false,
                'message' => 'Solo se pueden actualizar políticas en estado borrador.',
            ], 403);
        }

        // Obtener los datos validados (pueden ser parciales para el flujo de wizard)
        $validatedData = $request->validated();

        // Si hay wizard_data en la solicitud, fusionarlo con los datos existentes
        if (array_key_exists('wizard_data', $validatedData)) {
            // Fusionar los datos existentes con los nuevos datos parciales
            $mergedData = array_merge($policy->wizard_data, $validatedData['wizard_data']);
            $policy->update([
                'wizard_data' => $mergedData,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Borrador actualizado exitosamente.',
            'data' => $policy->fresh(), // Devolver el modelo actualizado
        ]);
    }

    /**
     * Archiva una política antigua.
     */
    public function archive(CompanyPolicy $policy, Request $request)
    {
        $this->authorize('archive', $policy);

        // Solo permitir archivar políticas publicadas
        if ($policy->status !== 'published') {
            return response()->json([
                'status' => false,
                'message' => 'Solo se pueden archivar políticas publicadas.',
            ], 403);
        }

        // Actualizar el estado a 'archived'
        $policy->update([
            'status' => 'archived',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Política archivada exitosamente.',
            'data' => $policy->fresh(),
        ]);
    }

    /**
     * Elimina una política archivada.
     */
    public function destroy(CompanyPolicy $policy, Request $request)
    {
        $this->authorize('delete', $policy);

        // Solo permitir eliminar políticas archivadas
        if ($policy->status !== 'archived') {
            return response()->json([
                'status' => false,
                'message' => 'Solo se pueden eliminar políticas archivadas.',
            ], 403);
        }

        // Eliminar el registro
        $policy->delete();

        return response()->json([
            'status' => true,
            'message' => 'Política eliminada exitosamente.',
        ]);
    }

    private function normalizeWizardData(array $wizardData, string $documentType): array
    {
        return match ($documentType) {
            'privacy_policy' => array_merge($wizardData, [
                'step_1_website_functions' => $this->mapWebsiteFunctions($wizardData['website_functions'] ?? []),
                'step_2_sensitive_data' => $wizardData['sensitive_data'] ?? [],
                'step_2_sensitive_data_other' => $wizardData['sensitive_data_other'] ?? null,
                'step_2_health_basis' => $wizardData['health_basis'] ?? null,
                'step_2_group_basis' => $wizardData['group_basis'] ?? null,
                'step_3_minors' => $wizardData['minors'] ?? [],
                'step_4_providers' => $wizardData['providers'] ?? [],
                'step_4_other_provider' => $wizardData['other_provider'] ?? null,
                'step_5_ai' => $wizardData['ai'] ?? [],
                'step_6_retention' => $wizardData['retention'] ?? [],
            ]),
            'cookie_policy' => array_merge($wizardData, [
                'step_2_analytics' => $wizardData['analytics'] ?? [],
                'step_3_marketing' => $wizardData['marketing'] ?? [],
                'step_4_functionality' => $wizardData['functionality'] ?? [],
            ]),
            'workers_policy' => array_merge($wizardData, [
                'step_1_monitoring' => $wizardData['monitoring'] ?? [],
                'step_2_health_benefits' => $wizardData['health_benefits'] ?? [],
                'step_3_sharing' => $wizardData['sharing'] ?? [],
            ]),
            'custom_policy' => array_merge($wizardData, [
                'custom_policy' => $wizardData['custom_policy'] ?? [],
            ]),
            default => $wizardData,
        };
    }

    private function mapWebsiteFunctions(array $functions): array
    {
        $frontendToTemplate = [
            'informative' => 'informativa',
            'ecommerce' => 'ecommerce',
            'saas' => 'saas',
        ];

        $result = [];
        foreach ($functions as $key => $value) {
            if ($value && isset($frontendToTemplate[$key])) {
                $result[] = $frontendToTemplate[$key];
            }
        }

        return $result;
    }
}
