<?php

use App\Models\Company;
use App\Models\CompanyPolicy;
use App\Models\ConsentLog;
use App\Models\LegalTemplate;
use App\Models\User;
use App\Services\Consent\ProofHashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/*
 * Helper: crea empresa con política publicada para testing.
 */
function createCompanyWithPolicy(): array
{
    $user = User::factory()->create();
    $company = Company::create([
        'user_id' => $user->id,
        'public_uuid' => Str::uuid(),
        'business_name' => 'Test Co',
        'integration_secret' => 'test-secret',
        'allowed_domains' => ['localhost'],
    ]);

    $template = LegalTemplate::create([
        'document_type' => 'cookie_policy',
        'name' => 'Política de Cookies Test',
        'version' => 1,
        'content' => 'Contenido de prueba',
        'is_active' => true,
    ]);

    $policy = CompanyPolicy::create([
        'company_id' => $company->id,
        'legal_template_id' => $template->id,
        'document_type' => 'cookie_policy',
        'company_version' => 1,
        'wizard_data' => ['purposes' => ['necessary_technical', 'analytics']],
        'integrity_hash' => hash('sha256', 'test-policy-hash'),
        'status' => 'published',
        'published_at' => now(),
    ]);

    return [$company, $policy];
}

/*
 * Helper: crea un consent_log con proof_hash válido.
 */
function createValidConsentLog(Company $company, CompanyPolicy $policy, ProofHashService $hashService): ConsentLog
{
    $visitorUuid = Str::uuid();
    $timestamp = now()->subMinutes(30)->toIso8601String();
    $purposes = ['necessary_technical' => true, 'analytics' => false];

    $payload = $hashService->buildPayload($visitorUuid, $policy->integrity_hash, $purposes, $timestamp);
    $proofHash = $hashService->compute($payload);

    return ConsentLog::create([
        'visitor_uuid' => $visitorUuid,
        'identifier' => $visitorUuid,
        'company_id' => $company->id,
        'company_policy_id' => $policy->id,
        'purposes' => $purposes,
        'policy_hash' => $policy->integrity_hash,
        'proof_hash' => $proofHash,
        'consent_occurred_at' => $timestamp,
        'capture_method' => 'live_widget',
        'ip_hash' => hash('sha256', '127.0.0.1'),
        'user_agent' => 'TestAgent/1.0',
    ]);
}

/*
 * Test: comando retorna SUCCESS cuando todos los registros están íntegros.
 */
test('verify-integrity returns success when all records are intact', function () {
    [$company, $policy] = createCompanyWithPolicy();
    $hashService = app(ProofHashService::class);

    createValidConsentLog($company, $policy, $hashService);
    createValidConsentLog($company, $policy, $hashService);

    $this->artisan('consent:verify-integrity')
        ->assertSuccessful()
        ->expectsOutputToContain('íntegro');
});

/*
 * Test: comando retorna FAILURE cuando hay registros adulterados.
 */
test('verify-integrity returns failure when records are adulterated', function () {
    [$company, $policy] = createCompanyWithPolicy();
    $hashService = app(ProofHashService::class);

    $log = createValidConsentLog($company, $policy, $hashService);

    // Adulterar el hash via DB::table (bypass del hook de inmutabilidad del modelo)
    DB::table('consent_logs')
        ->where('id', $log->id)
        ->update(['proof_hash' => str_repeat('a', 64)]);

    $this->artisan('consent:verify-integrity')
        ->assertFailed()
        ->expectsOutputToContain('ADULTERADO');
});

/*
 * Test: flag --fix imprime advertencia de inmutabilidad.
 */
test('fix flag prints immutability warning', function () {
    [$company, $policy] = createCompanyWithPolicy();
    $hashService = app(ProofHashService::class);

    $log = createValidConsentLog($company, $policy, $hashService);

    DB::table('consent_logs')
        ->where('id', $log->id)
        ->update(['proof_hash' => str_repeat('b', 64)]);

    $this->artisan('consent:verify-integrity --fix')
        ->assertFailed()
        ->expectsOutputToContain('El ledger es inmutable');
});

/*
 * Test: filtro --company solo verifica registros de esa empresa.
 */
test('company filter only checks specified company records', function () {
    [$company1, $policy1] = createCompanyWithPolicy();
    $hashService = app(ProofHashService::class);

    // Crear registro válido en company1
    createValidConsentLog($company1, $policy1, $hashService);

    // Crear segunda empresa con registro adulterado
    $user2 = User::factory()->create();
    $company2 = Company::create([
        'user_id' => $user2->id,
        'public_uuid' => Str::uuid(),
        'business_name' => 'Test Co 2',
        'allowed_domains' => ['localhost'],
    ]);

    $template2 = LegalTemplate::create([
        'document_type' => 'privacy_policy',
        'name' => 'Política de Privacidad Test',
        'version' => 1,
        'content' => 'Contenido de prueba 2',
        'is_active' => true,
    ]);

    $policy2 = CompanyPolicy::create([
        'company_id' => $company2->id,
        'legal_template_id' => $template2->id,
        'document_type' => 'privacy_policy',
        'company_version' => 1,
        'wizard_data' => ['purposes' => ['necessary_technical']],
        'integrity_hash' => hash('sha256', 'test-policy-hash-2'),
        'status' => 'published',
        'published_at' => now(),
    ]);

    $log2 = createValidConsentLog($company2, $policy2, $hashService);
    DB::table('consent_logs')->where('id', $log2->id)->update(['proof_hash' => str_repeat('c', 64)]);

    // Filtrar solo company1 → debe ser SUCCESS
    $this->artisan("consent:verify-integrity --company={$company1->id}")
        ->assertSuccessful();
});

/*
 * Test: sin registros, retorna SUCCESS con mensaje informativo.
 */
test('verify-integrity returns success when no records exist', function () {
    $this->artisan('consent:verify-integrity')
        ->assertSuccessful()
        ->expectsOutputToContain('No hay registros');
});
