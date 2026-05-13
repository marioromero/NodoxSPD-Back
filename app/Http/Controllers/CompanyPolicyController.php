<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyPolicyRequest;
use App\Http\Requests\UpdateCompanyPolicyRequest;
use App\Models\CompanyPolicy;
use App\Models\LegalTemplate;
use App\Services\PolicyMetricsService;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

class CompanyPolicyController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    /**
     * Métricas de políticas de la empresa.
     */
    public function metrics(Request $request)
    {
        $company = $request->user()->company;

        $metrics = PolicyMetricsService::count(
            (int) $company->id,
            $request->input('type')
        );

        return $this->success('Métricas de políticas obtenidas exitosamente.', $metrics);
    }

    /**
     * Lista el historial de políticas de la empresa.
     */
    public function index(Request $request)
    {
        $company = $request->user()->company;
        $query = CompanyPolicy::where('company_id', $company->id);

        if ($request->has('type')) {
            $query->where('document_type', $request->input('type'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('id')) {
            $query->where('id', $request->input('id'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        $policies = $query->with('template:id,name,version')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->success('Historial de políticas obtenido exitosamente.', $policies);
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

        return $this->success('Política generada exitosamente en borrador.', $policy->fresh(), 201);
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

            return $this->success('Documento compilado exitosamente.', [
                'html' => $htmlOutput,
                'document_type' => $policy->document_type,
                'version' => $policy->company_version,
                'status' => $policy->status,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error compilando documento legal', [
                'policy_id' => $policy->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            return $this->error('Error al compilar el documento legal.', null, 500);
        }
    }

    /**
     * Render público de una política publicada (compartir/incrustar).
     */
    public function publicRender(string $integrityHash)
    {
        $policy = CompanyPolicy::where('integrity_hash', $integrityHash)
            ->where('status', 'published')
            ->with(['company', 'template'])
            ->firstOrFail();

        try {
            $wizardData = $this->normalizeWizardData($policy->wizard_data ?? [], $policy->document_type);

            $htmlOutput = Blade::render($policy->template->content, [
                'company' => $policy->company,
                'policy' => $policy,
                'wizard_data' => $wizardData,
            ]);

            return response($htmlOutput, 200, ['Content-Type' => 'text/html']);

        } catch (\Throwable $e) {
            Log::error('Error compilando documento legal público', [
                'integrity_hash' => $integrityHash,
                'message' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            return response('Error al cargar el documento.', 500);
        }
    }

    /**
     * Publica la política y genera el Sello de Integridad (Hash).
     */
    public function publish(CompanyPolicy $policy, Request $request)
    {
        // Validar estado antes de authorize para evitar depender del exception handler
        if ($policy->status !== 'draft') {
            return $this->error('Solo se pueden publicar políticas en estado borrador.', null, 403);
        }

        $this->authorize('publish', $policy);

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

        return $this->success('Política publicada y sellada legalmente.', [
            'policy' => $policy->fresh(),
            'integrity_hash' => $integrityHash,
            'published_at' => $policy->published_at,
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

        return $this->success('Política obtenida exitosamente.', $policy->fresh());
    }

    /**
     * Actualiza un borrador de política.
     */
    public function update(UpdateCompanyPolicyRequest $request, CompanyPolicy $policy)
    {
        if ($policy->status !== 'draft') {
            return $this->error('Solo se pueden actualizar políticas en estado borrador.', null, 403);
        }

        $this->authorize('update', $policy);

        // Obtener los datos validados (pueden ser parciales para el flujo de wizard)
        $validatedData = $request->validated();

        // Si hay wizard_data en la solicitud, fusionarlo con los datos existentes
        if (array_key_exists('wizard_data', $validatedData)) {
            $mergedData = array_replace_recursive($policy->wizard_data ?? [], $validatedData['wizard_data']);
            $policy->update([
                'wizard_data' => $mergedData,
            ]);
        }

        return $this->success('Borrador actualizado exitosamente.', $policy->fresh());
    }

    /**
     * Archiva una política antigua.
     */
    public function archive(CompanyPolicy $policy, Request $request)
    {
        if ($policy->status !== 'published') {
            return $this->error('Solo se pueden archivar políticas publicadas.', null, 403);
        }

        $this->authorize('archive', $policy);

        // Actualizar el estado a 'archived'
        $policy->update([
            'status' => 'archived',
        ]);

        return $this->success('Política archivada exitosamente.', $policy->fresh());
    }

    /**
     * Elimina una política archivada.
     */
    public function destroy(CompanyPolicy $policy, Request $request)
    {
        if ($policy->status !== 'archived') {
            return $this->error('Solo se pueden eliminar políticas archivadas.', null, 403);
        }

        $this->authorize('delete', $policy);

        // Eliminar el registro
        $policy->delete();

        return $this->success('Política eliminada exitosamente.');
    }

    private function normalizeWizardData(array $wizardData, string $documentType): array
    {
        return match ($documentType) {
            'privacy_policy' => $this->normalizePrivacyPolicy($wizardData),
            'cookie_policy' => $this->normalizeCookiePolicy($wizardData),
            'workers_policy' => $this->normalizeWorkersPolicy($wizardData),
            'custom_policy' => array_merge($wizardData, [
                'custom_policy' => $this->normalizeCustomPolicy($wizardData),
            ]),
            default => $wizardData,
        };
    }

    private function normalizePrivacyPolicy(array $d): array
    {
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
        $providerKeys = ['google_analytics', 'meta', 'shopify', 'wix', 'mailchimp', 'hubspot', 'aws', 'azure', 'google_cloud', 'otros'];
        foreach ($providerKeys as $key) {
            if ($d["step_4_providers_{$key}"] ?? false) {
                $providers[] = $key;
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
        $analytics = [];
        if ($d['step_2_analytics_active'] ?? false) {
            $analytics['active'] = true;
            $providers = [];
            $providerKeys = ['google_analytics', 'hotjar', 'mixpanel', 'clarity', 'matomo', 'otros'];
            foreach ($providerKeys as $key) {
                if ($d["step_2_analytics_provider_{$key}"] ?? false) {
                    $providers[] = $key;
                }
            }
            $analytics['providers'] = $providers;
            $analytics['other_provider'] = $d['step_2_analytics_other_provider'] ?? null;
        }

        $marketing = [];
        if ($d['step_3_marketing_active'] ?? false) {
            $marketing['active'] = true;
            $providers = [];
            $providerKeys = ['meta_pixel', 'google_ads', 'tiktok_pixel', 'linkedin_insight', 'twitter_pixel', 'otros'];
            foreach ($providerKeys as $key) {
                if ($d["step_3_marketing_provider_{$key}"] ?? false) {
                    $providers[] = $key;
                }
            }
            $marketing['providers'] = $providers;
            $marketing['other_provider'] = $d['step_3_marketing_other_provider'] ?? null;
        }

        $functionality = [];
        if ($d['step_4_functionality_active'] ?? false) {
            $functionality['active'] = true;
            $providers = [];
            $providerKeys = ['youtube', 'maps', 'whatsapp', 'social', 'fonts', 'otros'];
            foreach ($providerKeys as $key) {
                if ($d["step_4_functionality_provider_{$key}"] ?? false) {
                    $providers[] = $key;
                }
            }
            $functionality['providers'] = $providers;
            $functionality['other_provider'] = $d['step_4_functionality_other_provider'] ?? null;
        }

        return array_merge($d, [
            'step_2_analytics' => $analytics,
            'step_3_marketing' => $marketing,
            'step_4_functionality' => $functionality,
        ]);
    }

    private function normalizeWorkersPolicy(array $d): array
    {
        $monitoring = [];
        if ($d['step_1_monitoring_video'] ?? false) {
            $monitoring['video'] = true;
        }
        if ($d['step_1_monitoring_biometrics'] ?? false) {
            $monitoring['biometrics'] = true;
            $monitoring['biometrics_system'] = $d['step_1_monitoring_biometrics_system'] ?? null;
        }
        if ($d['step_1_monitoring_gps'] ?? false) {
            $monitoring['gps'] = true;
        }
        if ($d['step_1_monitoring_digital'] ?? false) {
            $monitoring['digital'] = true;
        }

        $healthBenefits = [];
        if ($d['step_2_health_benefits_health_active'] ?? false) {
            $healthBenefits['health_active'] = true;
        }
        if ($d['step_2_health_benefits_benefits_active'] ?? false) {
            $healthBenefits['benefits_active'] = true;
        }

        $sharing = [];
        $sharing['none'] = $d['step_3_sharing_none'] ?? false;
        $sharing['social_security'] = $d['step_3_sharing_social_security'] ?? true;
        if ($d['step_3_sharing_hr_software'] ?? false) {
            $sharing['hr_software'] = true;
            $sharing['hr_software_names'] = $d['step_3_sharing_hr_software_names'] ?? null;
        }
        if ($d['step_3_sharing_external_advisors'] ?? false) {
            $sharing['external_advisors'] = true;
            $sharing['external_advisors_names'] = $d['step_3_sharing_external_advisors_names'] ?? null;
        }
        if ($d['step_3_sharing_others'] ?? false) {
            $sharing['others'] = true;
            $sharing['others_names'] = $d['step_3_sharing_others_names'] ?? null;
            $sharing['others_purpose'] = $d['step_3_sharing_others_purpose'] ?? null;
        }

        return array_merge($d, [
            'step_1_monitoring' => $monitoring,
            'step_2_health_benefits' => $healthBenefits,
            'step_3_sharing' => $sharing,
        ]);
    }

    private function normalizeCustomPolicy(array $d): array
    {
        if (isset($d['custom_policy']['title'])) {
            return $d['custom_policy'];
        }

        $custom = [];
        $custom['title'] = $d['custom_policy_title'] ?? 'DOCUMENTO LEGAL PERSONALIZADO';
        $custom['is_privacy_related'] = $d['custom_policy_is_privacy_related'] ?? true;
        $custom['free_text_html'] = $d['custom_policy_free_text_html'] ?? null;
        $custom['context'] = $d['custom_policy_context'] ?? null;
        $custom['data_categories'] = $d['custom_policy_data_categories'] ?? null;
        $custom['purposes'] = $d['custom_policy_purposes'] ?? null;
        $custom['legal_basis'] = $d['custom_policy_legal_basis'] ?? null;
        $custom['recipients'] = $d['custom_policy_recipients'] ?? null;
        $custom['international_transfers'] = $d['custom_policy_international_transfers'] ?? null;
        $custom['retention_period'] = $d['custom_policy_retention_period'] ?? null;

        return $custom;
    }
}
