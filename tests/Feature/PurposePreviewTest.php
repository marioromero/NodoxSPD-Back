<?php

use App\Models\ConsentPurpose;
use App\Models\LegalTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedPurposes();
});

/*
 * Helper: siembra los consent purposes esenciales para los tests.
 */
function seedPurposes(): void
{
    $purposes = [
        ['slug' => 'necessary_technical', 'category' => 'gestion_general', 'label' => 'Funcionamiento del sitio', 'description' => 'Test', 'legal_basis' => 'legitimate_interest', 'requires_consent' => false, 'default_value' => true, 'display_order' => 0, 'is_active' => true],
        ['slug' => 'service_improvement', 'category' => 'gestion_general', 'label' => 'Seguridad', 'description' => 'Test', 'legal_basis' => 'legitimate_interest', 'requires_consent' => false, 'default_value' => true, 'display_order' => 6, 'is_active' => true],
        ['slug' => 'contractual_execution', 'category' => 'gestion_general', 'label' => 'Prestación del servicio', 'description' => 'Test', 'legal_basis' => 'contractual', 'requires_consent' => false, 'default_value' => true, 'display_order' => 9, 'is_active' => true],
        ['slug' => 'legal_compliance', 'category' => 'gestion_general', 'label' => 'Obligaciones legales', 'description' => 'Test', 'legal_basis' => 'legal_obligation', 'requires_consent' => false, 'default_value' => true, 'display_order' => 10, 'is_active' => true],
        ['slug' => 'marketing_profiling', 'category' => 'comercial_marketing', 'label' => 'Perfilamiento', 'description' => 'Test', 'legal_basis' => 'consent', 'requires_consent' => true, 'default_value' => false, 'display_order' => 4, 'is_active' => true],
    ];

    foreach ($purposes as $p) {
        ConsentPurpose::create($p);
    }
}

/*
 * Helper: crea template legal activo para privacy_policy con wizard_schema real.
 */
function createPrivacyTemplate(): LegalTemplate
{
    $template = LegalTemplate::create([
        'document_type' => 'privacy_policy',
        'name' => 'Política de Privacidad Web Test',
        'version' => 1,
        'content' => '<p>Test content</p>',
        'wizard_schema' => [
            'steps' => [
                [
                    'fields' => [
                        [
                            'key' => 'step_1_website_functions',
                            'type' => 'multiselect',
                            'options' => [
                                'informativa' => ['label' => 'Informativa', 'legal_purposes' => ['contractual_execution']],
                                'ecommerce' => ['label' => 'E-commerce', 'legal_purposes' => ['contractual_execution', 'legal_compliance']],
                                'saas' => ['label' => 'SaaS', 'legal_purposes' => ['contractual_execution', 'service_improvement']],
                            ],
                        ],
                    ],
                ],
                [
                    'fields' => [
                        [
                            'key' => 'step_5_ai_active',
                            'type' => 'boolean',
                            'legal_purposes' => ['marketing_profiling'],
                        ],
                    ],
                ],
            ],
        ],
        'is_active' => true,
    ]);

    return $template;
}

/*
 * Helper: crea usuario + empresa para autenticación Sanctum.
 */
function createAuthUser(): array
{
    $user = User::factory()->create();

    return [$user, 'password'];
}

/*
 * Test: preview con wizard_data completo (informativa) retorna solo 2 purposes.
 */
test('preview with informativa returns necessary_technical and contractual_execution only', function () {
    createPrivacyTemplate();
    [$user] = createAuthUser();

    $response = $this->actingAs($user)->postJson('/api/panel/purposes/preview', [
        'document_type' => 'privacy_policy',
        'wizard_data' => [
            'step_1_website_functions' => ['informativa'],
        ],
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('status', true)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'necessary_technical')
        ->assertJsonPath('data.1.slug', 'contractual_execution');
});

/*
 * Test: preview con ecommerce agrega legal_compliance.
 */
test('preview with ecommerce adds legal_compliance', function () {
    createPrivacyTemplate();
    [$user] = createAuthUser();

    $response = $this->actingAs($user)->postJson('/api/panel/purposes/preview', [
        'document_type' => 'privacy_policy',
        'wizard_data' => [
            'step_1_website_functions' => ['ecommerce'],
        ],
    ]);

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.2.slug', 'legal_compliance');
});

/*
 * Test: preview con wizard_data vacio retorna solo necessary_technical.
 */
test('preview with empty wizard_data returns only necessary_technical', function () {
    createPrivacyTemplate();
    [$user] = createAuthUser();

    $response = $this->actingAs($user)->postJson('/api/panel/purposes/preview', [
        'document_type' => 'privacy_policy',
        'wizard_data' => [],
    ]);

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.slug', 'necessary_technical');
});

/*
 * Test: preview con boolean true activa marketing_profiling.
 */
test('preview with boolean field true activates marketing_profiling', function () {
    createPrivacyTemplate();
    [$user] = createAuthUser();

    $response = $this->actingAs($user)->postJson('/api/panel/purposes/preview', [
        'document_type' => 'privacy_policy',
        'wizard_data' => [
            'step_1_website_functions' => ['informativa'],
            'step_5_ai_active' => true,
        ],
    ]);

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.slug', 'necessary_technical')
        ->assertJsonPath('data.1.slug', 'marketing_profiling')
        ->assertJsonPath('data.2.slug', 'contractual_execution');
});

/*
 * Test: sin autenticación retorna 401.
 */
test('unauthenticated request returns 401', function () {
    $response = $this->postJson('/api/panel/purposes/preview', [
        'document_type' => 'privacy_policy',
        'wizard_data' => [],
    ]);

    $response->assertStatus(401);
});

/*
 * Test: document_type inválido retorna 422.
 */
test('invalid document_type returns 422', function () {
    [$user] = createAuthUser();

    $response = $this->actingAs($user)->postJson('/api/panel/purposes/preview', [
        'document_type' => 'invalid_type',
        'wizard_data' => [],
    ]);

    $response->assertStatus(422);
});

/*
 * Test: wizard_data ausente retorna 422.
 */
test('missing wizard_data returns 422', function () {
    [$user] = createAuthUser();

    $response = $this->actingAs($user)->postJson('/api/panel/purposes/preview', [
        'document_type' => 'privacy_policy',
    ]);

    $response->assertStatus(422);
});

/*
 * Test: template inexistente retorna 404.
 */
test('no active template returns 404', function () {
    [$user] = createAuthUser();

    $response = $this->actingAs($user)->postJson('/api/panel/purposes/preview', [
        'document_type' => 'workers_policy',
        'wizard_data' => [],
    ]);

    $response->assertStatus(404);
});

/*
 * Test: combinación de informativa + saas no duplica contractual_execution.
 */
test('informativa plus saas does not duplicate contractual_execution', function () {
    createPrivacyTemplate();
    [$user] = createAuthUser();

    $response = $this->actingAs($user)->postJson('/api/panel/purposes/preview', [
        'document_type' => 'privacy_policy',
        'wizard_data' => [
            'step_1_website_functions' => ['informativa', 'saas'],
        ],
    ]);

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.slug', 'necessary_technical')
        ->assertJsonPath('data.1.slug', 'service_improvement')
        ->assertJsonPath('data.2.slug', 'contractual_execution');
});
