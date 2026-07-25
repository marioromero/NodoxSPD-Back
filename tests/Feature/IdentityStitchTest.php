<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/*
 * Helper: crea una empresa con integration_secret y dominios para testing.
 */
function createTestCompany(?string $secret = 'test-integration-secret-key'): Company
{
    $user = User::factory()->create();

    return Company::create([
        'user_id' => $user->id,
        'public_uuid' => Str::uuid(),
        'business_name' => 'Test Company SPA',
        'integration_secret' => $secret,
        'allowed_domains' => ['example.com'],
    ]);
}

/*
 * Helper: genera payload válido con HMAC firmado para el bloque de tiempo actual.
 */
function buildValidSyncPayload(Company $company, string $userRef = 'user-123'): array
{
    $block = (int) floor(time() / 300);
    $hmac = hash_hmac('sha256', $userRef.':'.$block, $company->integration_secret);

    return [
        'visitor_uuid' => '550e8400-e29b-41d4-a716-446655440000',
        'company_public_uuid' => $company->public_uuid,
        'user_ref' => $userRef,
        'timestamp' => time(),
        'hmac' => $hmac,
    ];
}

/*
 * Test: POST con HMAC válido retorna 200 {"status": "synced"} y crea registro.
 */
test('valid hmac returns 200 synced and creates record', function () {
    $company = createTestCompany();
    $payload = buildValidSyncPayload($company);

    $response = $this->withHeaders([
        'Origin' => 'http://example.com',
    ])->postJson("/api/widget/{$company->public_uuid}/identity-sync", $payload);

    $response->assertStatus(200)
        ->assertJson(['status' => 'synced']);

    $this->assertDatabaseHas('person_visitor_uuids', [
        'company_id' => $company->id,
        'visitor_uuid' => $payload['visitor_uuid'],
        'person_id' => null,
    ]);
});

/*
 * Test: POST con HMAC inválido retorna 401 invalid_hmac.
 */
test('invalid hmac returns 401', function () {
    $company = createTestCompany();
    $payload = buildValidSyncPayload($company);
    $payload['hmac'] = str_repeat('0', 64);

    $response = $this->withHeaders([
        'Origin' => 'http://example.com',
    ])->postJson("/api/widget/{$company->public_uuid}/identity-sync", $payload);

    $response->assertStatus(401)
        ->assertJson(['error' => 'invalid_hmac']);
});

/*
 * Test: POST con timestamp expirado retorna 401 hmac_expired.
 */
test('expired timestamp returns 401 hmac_expired', function () {
    $company = createTestCompany();
    $oldBlock = (int) floor((time() - 600) / 300);
    $hmac = hash_hmac('sha256', 'user-123:'.$oldBlock, $company->integration_secret);

    $payload = [
        'visitor_uuid' => '550e8400-e29b-41d4-a716-446655440000',
        'company_public_uuid' => $company->public_uuid,
        'user_ref' => 'user-123',
        'timestamp' => time() - 600,
        'hmac' => $hmac,
    ];

    $response = $this->withHeaders([
        'Origin' => 'http://example.com',
    ])->postJson("/api/widget/{$company->public_uuid}/identity-sync", $payload);

    $response->assertStatus(401)
        ->assertJson(['error' => 'hmac_expired']);
});

/*
 * Test: empresa sin integration_secret retorna 404 widget_not_configured.
 */
test('company without integration_secret returns 404', function () {
    $user = User::factory()->create();
    $company = Company::create([
        'user_id' => $user->id,
        'public_uuid' => Str::uuid(),
        'business_name' => 'No Secret Co',
        'allowed_domains' => ['example.com'],
    ]);

    $payload = [
        'visitor_uuid' => '550e8400-e29b-41d4-a716-446655440000',
        'company_public_uuid' => $company->public_uuid,
        'user_ref' => 'user-123',
        'timestamp' => time(),
        'hmac' => str_repeat('a', 64),
    ];

    $response = $this->withHeaders([
        'Origin' => 'http://example.com',
    ])->postJson("/api/widget/{$company->public_uuid}/identity-sync", $payload);

    $response->assertStatus(404)
        ->assertJson(['error' => 'widget_not_configured']);
});

/*
 * Test: reenviar el mismo payload no duplica registros (idempotencia via upsert).
 */
test('resending same payload does not duplicate records', function () {
    $company = createTestCompany();
    $payload = buildValidSyncPayload($company);

    $first = $this->withHeaders(['Origin' => 'http://example.com'])
        ->postJson("/api/widget/{$company->public_uuid}/identity-sync", $payload);

    $first->assertStatus(200);

    $second = $this->withHeaders(['Origin' => 'http://example.com'])
        ->postJson("/api/widget/{$company->public_uuid}/identity-sync", $payload);

    $second->assertStatus(200);

    $count = DB::table('person_visitor_uuids')
        ->where('company_id', $company->id)
        ->where('visitor_uuid', $payload['visitor_uuid'])
        ->count();

    expect($count)->toBe(1);
});

/*
 * Test: CORS preflight OPTIONS retorna 204 con headers correctos.
 */
test('cors preflight returns 204 with correct headers', function () {
    $company = createTestCompany();

    $response = $this->withHeaders([
        'Origin' => 'http://example.com',
        'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'Content-Type',
    ])->options("/api/widget/{$company->public_uuid}/identity-sync");

    $response->assertStatus(204)
        ->assertHeader('Access-Control-Allow-Origin', 'http://example.com')
        ->assertHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
});

/*
 * Test: CORS rechaza origin no permitido con 403.
 */
test('cors rejects disallowed origin with 403', function () {
    $company = createTestCompany();
    $payload = buildValidSyncPayload($company);

    $response = $this->withHeaders([
        'Origin' => 'http://evil.com',
    ])->postJson("/api/widget/{$company->public_uuid}/identity-sync", $payload);

    $response->assertStatus(403);
});
