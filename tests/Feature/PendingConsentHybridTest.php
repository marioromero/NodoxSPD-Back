<?php

use App\Models\Company;
use App\Models\CompanyBatch;
use App\Models\CompanyPolicy;
use App\Models\LegalTemplate;
use App\Models\PendingConsent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/*
 * Helper: crea empresa con política publicada para testing.
 */
function createCompanyWithPolicyForBatch(): array
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
        'wizard_data' => ['purposes' => ['necessary_technical']],
        'integrity_hash' => hash('sha256', 'test-policy-hash'),
        'status' => 'published',
        'published_at' => now(),
    ]);

    return [$user, $company, $policy];
}

/*
 * Camino A — 1 correo: mode=single, status=created.
 */
test('single email returns mode single with status created', function () {
    [$user, $company, $policy] = createCompanyWithPolicyForBatch();

    Bus::fake();

    $response = $this->actingAs($user)
        ->postJson('/api/panel/pending-consents', [
            'company_policy_id' => $policy->id,
            'emails' => ['juan@example.com'],
        ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.mode', 'single')
        ->assertJsonPath('data.status', 'created');

    expect(PendingConsent::count())->toBe(1);
});

/*
 * Camino A — 1 correo ya pendiente: mode=single, status=already_pending.
 */
test('single email already pending returns already_pending', function () {
    [$user, $company, $policy] = createCompanyWithPolicyForBatch();

    PendingConsent::create([
        'company_id' => $company->id,
        'company_policy_id' => $policy->id,
        'token' => PendingConsent::generateToken(),
        'pii_payload' => ['email' => 'juan@example.com'],
        'pii_hash' => hash('sha256', 'juan@example.com'),
        'status' => 'pending',
        'source' => 'manual_panel',
        'expires_at' => now()->addDays(7),
    ]);

    Bus::fake();

    $response = $this->actingAs($user)
        ->postJson('/api/panel/pending-consents', [
            'company_policy_id' => $policy->id,
            'emails' => ['juan@example.com'],
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.mode', 'single')
        ->assertJsonPath('data.status', 'already_pending');

    expect(PendingConsent::count())->toBe(1);
});

/*
 * Camino B — >1 correo: mode=batch, status=queued.
 */
test('multiple emails return mode batch with status queued', function () {
    [$user, $company, $policy] = createCompanyWithPolicyForBatch();

    Bus::fake();

    $emails = ['a@example.com', 'b@example.com', 'c@example.com'];

    $response = $this->actingAs($user)
        ->postJson('/api/panel/pending-consents', [
            'company_policy_id' => $policy->id,
            'emails' => $emails,
        ]);

    $response->assertStatus(202)
        ->assertJsonPath('data.mode', 'batch')
        ->assertJsonPath('data.status', 'queued')
        ->assertJsonStructure(['data' => ['batch_id', 'total']]);
});

/*
 * Camino B — <=20 correos usa cola consent-emails-priority.
 */
test('batch with <=20 emails uses consent-emails-priority queue', function () {
    [$user, $company, $policy] = createCompanyWithPolicyForBatch();

    Bus::fake();

    $emails = array_map(fn ($i) => "user{$i}@example.com", range(1, 5));

    $response = $this->actingAs($user)
        ->postJson('/api/panel/pending-consents', [
            'company_policy_id' => $policy->id,
            'emails' => $emails,
        ]);

    $response->assertStatus(202)
        ->assertJsonPath('data.mode', 'batch');
});

/*
 * Camino B — duplicados internos del request se filtran.
 */
test('duplicate emails in request are deduplicated', function () {
    [$user, $company, $policy] = createCompanyWithPolicyForBatch();

    Bus::fake();

    $response = $this->actingAs($user)
        ->postJson('/api/panel/pending-consents', [
            'company_policy_id' => $policy->id,
            'emails' => ['dup@example.com', 'dup@example.com', 'unique@example.com'],
        ]);

    $response->assertStatus(202)
        ->assertJsonPath('data.total', 2);
});

/*
 * Política no publicada retorna 403.
 */
test('unpublished policy returns 403', function () {
    [$user, $company, $policy] = createCompanyWithPolicyForBatch();

    $policy->update(['status' => 'draft']);

    $response = $this->actingAs($user)
        ->postJson('/api/panel/pending-consents', [
            'company_policy_id' => $policy->id,
            'emails' => ['test@example.com'],
        ]);

    $response->assertStatus(403);
});

/*
 * Política de otra empresa retorna 403.
 */
test('policy from another company returns 403', function () {
    [$user, $company, $policy] = createCompanyWithPolicyForBatch();

    $user2 = User::factory()->create();
    $company2 = Company::create([
        'user_id' => $user2->id,
        'public_uuid' => Str::uuid(),
        'business_name' => 'Other Co',
        'allowed_domains' => ['localhost'],
    ]);

    $template2 = LegalTemplate::create([
        'document_type' => 'privacy_policy',
        'name' => 'Privacy Test',
        'version' => 1,
        'content' => 'content',
        'is_active' => true,
    ]);

    $policy2 = CompanyPolicy::create([
        'company_id' => $company2->id,
        'legal_template_id' => $template2->id,
        'document_type' => 'privacy_policy',
        'company_version' => 1,
        'wizard_data' => [],
        'integrity_hash' => hash('sha256', 'other-hash'),
        'status' => 'published',
        'published_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->postJson('/api/panel/pending-consents', [
            'company_policy_id' => $policy2->id,
            'emails' => ['test@example.com'],
        ]);

    $response->assertStatus(403);
});

/*
 * GET /api/panel/batches/{id} retorna 404 si no pertenece a la empresa.
 */
test('batch progress returns 404 for batch from another company', function () {
    [$user, $company, $policy] = createCompanyWithPolicyForBatch();

    $user2 = User::factory()->create();
    $company2 = Company::create([
        'user_id' => $user2->id,
        'public_uuid' => Str::uuid(),
        'business_name' => 'Other Co',
        'allowed_domains' => ['localhost'],
    ]);

    CompanyBatch::create([
        'company_id' => $company2->id,
        'batch_id' => 'fake-batch-id',
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/panel/batches/fake-batch-id');

    $response->assertStatus(404);
});

/*
 * Webhook de Resend sin secret configurado retorna 503.
 */
test('resend webhook returns 503 when secret not configured', function () {
    config(['services.resend.webhook_secret' => null]);

    $response = $this->postJson('/api/webhook/resend', []);

    $response->assertStatus(503);
});

/*
 * Webhook de Resend con firma inválida retorna 401.
 */
test('resend webhook returns 401 for invalid signature', function () {
    config(['services.resend.webhook_secret' => 'test-secret']);

    $response = $this->postJson('/api/webhook/resend', [
        'event' => 'email.bounced',
        'data' => ['to' => 'bounce@example.com'],
    ], [
        'svix-signature' => 'v1=invalid',
        'svix-timestamp' => (string) time(),
    ]);

    $response->assertStatus(401);
});

/*
 * Webhook de Resend marca PendingConsent como bounced.
 */
test('resend webhook marks pending consent as bounced', function () {
    config(['services.resend.webhook_secret' => 'test-secret']);

    [$user, $company, $policy] = createCompanyWithPolicyForBatch();

    $pendingConsent = PendingConsent::create([
        'company_id' => $company->id,
        'company_policy_id' => $policy->id,
        'token' => PendingConsent::generateToken(),
        'pii_payload' => ['email' => 'bounce@example.com'],
        'pii_hash' => hash('sha256', 'bounce@example.com'),
        'status' => 'pending',
        'source' => 'manual_panel',
        'expires_at' => now()->addDays(7),
    ]);

    $body = json_encode([
        'event' => 'email.bounced',
        'data' => ['to' => 'bounce@example.com'],
    ]);

    $timestamp = (string) time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$body}", 'test-secret');

    $response = $this->call('POST', '/api/webhook/resend', [], [], [], [
        'HTTP_svix-signature' => "v1={$signature}",
        'HTTP_svix-timestamp' => $timestamp,
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    $response->assertStatus(200);

    expect($pendingConsent->fresh()->status)->toBe('bounced');
});
