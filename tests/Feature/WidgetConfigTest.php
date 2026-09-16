<?php

use App\Models\Company;
use App\Models\CompanyPolicy;
use App\Models\ConsentPurpose;
use App\Models\LegalTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/*
 * Helper: siembra los consent purposes con widget_action representativos.
 */
function seedWidgetPurposes(): void
{
    $purposes = [
        ['slug' => 'necessary_technical', 'category' => 'gestion_general', 'label' => 'Funcionamiento del sitio', 'description' => 'Test', 'legal_basis' => 'legitimate_interest', 'requires_consent' => false, 'default_value' => true, 'display_order' => 0, 'is_active' => true, 'widget_action' => null],
        ['slug' => 'analytics_behavior', 'category' => 'analisis_comportamiento', 'label' => 'Estadísticas de visitas', 'description' => 'Test', 'legal_basis' => 'consent', 'requires_consent' => true, 'default_value' => false, 'display_order' => 1, 'is_active' => true, 'widget_action' => 'load_analytics_scripts'],
    ];

    foreach ($purposes as $p) {
        ConsentPurpose::create($p);
    }
}

/*
 * Helper: crea empresa con política de cookies publicada cuyo wizard activa analytics_behavior.
 */
function createCompanyWithWidgetPolicy(): Company
{
    $user = User::factory()->create();

    $template = LegalTemplate::create([
        'document_type' => 'cookie_policy',
        'name' => 'Política de Cookies Test',
        'version' => 1,
        'content' => 'Contenido de prueba',
        'is_active' => true,
        'wizard_schema' => [
            'steps' => [
                [
                    'fields' => [
                        ['key' => 'step_2_analytics_enabled', 'type' => 'boolean', 'legal_purposes' => ['analytics_behavior']],
                    ],
                ],
            ],
        ],
    ]);

    $company = Company::create([
        'user_id' => $user->id,
        'public_uuid' => Str::uuid(),
        'business_name' => 'Widget Test Co',
        'integration_secret' => 'test-secret',
        'allowed_domains' => ['localhost'],
    ]);

    CompanyPolicy::create([
        'company_id' => $company->id,
        'legal_template_id' => $template->id,
        'document_type' => 'cookie_policy',
        'company_version' => 1,
        'wizard_data' => ['step_2_analytics_enabled' => true],
        'integrity_hash' => hash('sha256', 'widget-test-policy'),
        'status' => 'published',
        'published_at' => now(),
    ]);

    return $company;
}

/*
 * Test: /config expone widget_action por propósito (el widget lo usa para el mapeo de acciones).
 */
test('widget config expone widget_action por proposito', function () {
    seedWidgetPurposes();
    $company = createCompanyWithWidgetPolicy();

    $response = $this->getJson("/api/widget/{$company->public_uuid}/config");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'policy_hash',
            'policy_version',
            'widget_config' => ['position', 'primary_color', 'show_reject_all', 'cookie_duration_days', 'providers'],
            'purposes' => [
                'necessary_technical' => ['label', 'description', 'required', 'default', 'legal_basis', 'widget_action'],
                'analytics_behavior' => ['label', 'description', 'required', 'default', 'legal_basis', 'widget_action'],
            ],
            'legal_texts',
        ])
        ->assertJsonPath('policy_version', 1)
        ->assertJsonPath('purposes.necessary_technical.widget_action', null)
        ->assertJsonPath('purposes.necessary_technical.required', true)
        ->assertJsonPath('purposes.analytics_behavior.widget_action', 'load_analytics_scripts')
        ->assertJsonPath('purposes.analytics_behavior.label', 'Estadísticas de visitas')
        ->assertJsonPath('purposes.analytics_behavior.required', false);
});

/*
 * Test: /config retorna 404 cuando la empresa no tiene política de cookies publicada.
 */
test('widget config retorna 404 sin politica publicada', function () {
    seedWidgetPurposes();
    $user = User::factory()->create();
    $uuid = Str::uuid();

    Company::create([
        'user_id' => $user->id,
        'public_uuid' => $uuid,
        'business_name' => 'Sin Politica Co',
        'allowed_domains' => ['localhost'],
    ]);

    $this->getJson("/api/widget/{$uuid}/config")
        ->assertStatus(404)
        ->assertJsonPath('status', false)
        ->assertJsonPath('message', 'Esta empresa no tiene una política de cookies publicada.');
});

/*
 * Test: /config rechaza un identificador que no es UUID v4.
 */
test('widget config rechaza identificador invalido', function () {
    $this->getJson('/api/widget/no-es-uuid/config')
        ->assertStatus(400)
        ->assertJsonPath('status', false);
});
