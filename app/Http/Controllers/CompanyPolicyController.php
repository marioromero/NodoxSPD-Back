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
            $mergedData = array_replace_recursive($policy->wizard_data ?? [], $validatedData['wizard_data']);
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
            'privacy_policy' => $this->normalizePrivacyPolicy($wizardData),
            'cookie_policy' => $this->normalizeCookiePolicy($wizardData),
            'workers_policy' => $this->normalizeWorkersPolicy($wizardData),
            'custom_policy' => array_merge($wizardData, [
                'custom_policy' => $wizardData['custom_policy'] ?? [],
            ]),
            default => $wizardData,
        };
    }

    private function normalizePrivacyPolicy(array $d): array
    {
        if (isset($d['website_functions']) || isset($d['sensitive_data'])) {
            return array_merge($d, [
                'step_1_website_functions' => $this->mapWebsiteFunctions($d['website_functions'] ?? []),
                'step_2_sensitive_data' => $d['sensitive_data'] ?? [],
                'step_2_sensitive_data_other' => $d['sensitive_data_other'] ?? null,
                'step_2_health_basis' => $d['health_basis'] ?? null,
                'step_2_group_basis' => $d['group_basis'] ?? null,
                'step_3_minors' => $d['minors'] ?? [],
                'step_4_providers' => $d['providers'] ?? [],
                'step_4_other_provider' => $d['other_provider'] ?? null,
                'step_5_ai' => $d['ai'] ?? [],
                'step_6_retention' => $d['retention'] ?? [],
            ]);
        }

        $websiteFunctions = [];
        $map = ['informative' => 'informativa', 'ecommerce' => 'ecommerce', 'saas' => 'saas'];
        foreach ($map as $flat => $blade) {
            if ($d["step_1_website_functions_{$flat}"] ?? false) {
                $websiteFunctions[] = $blade;
            }
        }

        $sensitiveData = [];
        $sensitiveKeys = ['salud', 'biometria', 'politica', 'sindical', 'religion', 'sexual', 'racial', 'otros', 'ninguna'];
        foreach ($sensitiveKeys as $key) {
            if ($d["step_2_sensitive_data_{$key}"] ?? false) {
                $sensitiveData[] = $key;
            }
        }

        $minors = [];
        if ($d['step_3_minors_active'] ?? false) {
            $minors['active'] = true;
            $purposes = [];
            $purposeKeys = ['servicio', 'seguridad', 'legal', 'marketing', 'otros'];
            foreach ($purposeKeys as $key) {
                if ($d["step_3_minors_purposes_{$key}"] ?? false) {
                    $purposes[] = $key;
                }
            }
            $minors['purposes'] = $purposes;
            $minors['other_purpose'] = $d['step_3_minors_other_purpose'] ?? null;
            $minors['verification_method'] = $d['step_3_minors_verification_method'] ?? null;
        }

        $providers = [];
        if ($d['step_4_providers_foreign'] ?? false) {
            $providerKeys = ['google_analytics', 'meta', 'shopify', 'wix', 'mailchimp', 'hubspot', 'aws', 'azure', 'google_cloud'];
            foreach ($providerKeys as $key) {
                if ($d["step_4_providers_{$key}"] ?? false) {
                    $providers[] = $key;
                }
            }
        }
        if ($d['step_4_providers_local'] ?? false) {
            $providers[] = 'local';
        }

        $ai = [];
        if ($d['step_5_ai_active'] ?? false) {
            $ai['active'] = true;
            $ai['parameters'] = $d['step_5_ai_parameters'] ?? null;
            $ai['logic'] = $d['step_5_ai_logic'] ?? null;
            $ai['consequences'] = $d['step_5_ai_consequences'] ?? null;
        }

        $retention = [];
        if ($d['step_6_retention_tax_commercial'] ?? false) {
            $retention['tax_commercial'] = true;
        }
        if ($d['step_6_retention_user_accounts'] ?? false) {
            $retention['user_accounts'] = true;
            $retention['account_days'] = $d['step_6_retention_account_days'] ?? '30';
        }
        if ($d['step_6_retention_marketing'] ?? false) {
            $retention['marketing'] = true;
        }
        if ($d['step_6_retention_custom'] ?? false) {
            $retention['custom'] = true;
            $retention['custom_period'] = $d['step_6_retention_custom_period'] ?? null;
        }

        return array_merge($d, [
            'step_1_website_functions' => $websiteFunctions,
            'step_2_sensitive_data' => $sensitiveData,
            'step_2_sensitive_data_other' => $d['step_2_sensitive_data_other'] ?? null,
            'step_2_health_basis' => $d['step_2_health_basis'] ?? null,
            'step_2_group_basis' => $d['step_2_group_basis'] ?? null,
            'step_3_minors' => $minors,
            'step_4_providers' => $providers,
            'step_4_other_provider' => $d['step_4_other_provider'] ?? null,
            'step_5_ai' => $ai,
            'step_6_retention' => $retention,
        ]);
    }

    private function normalizeCookiePolicy(array $d): array
    {
        return array_merge($d, [
            'step_2_analytics' => $d['analytics'] ?? $d['step_2_analytics'] ?? [],
            'step_3_marketing' => $d['marketing'] ?? $d['step_3_marketing'] ?? [],
            'step_4_functionality' => $d['functionality'] ?? $d['step_4_functionality'] ?? [],
        ]);
    }

    private function normalizeWorkersPolicy(array $d): array
    {
        return array_merge($d, [
            'step_1_monitoring' => $d['monitoring'] ?? $d['step_1_monitoring'] ?? [],
            'step_2_health_benefits' => $d['health_benefits'] ?? $d['step_2_health_benefits'] ?? [],
            'step_3_sharing' => $d['sharing'] ?? $d['step_3_sharing'] ?? [],
        ]);
    }
}
