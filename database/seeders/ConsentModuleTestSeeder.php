<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyPolicy;
use App\Models\ConsentLog;
use App\Models\PendingConsent;
use App\Services\Consent\ProofHashService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder de prueba para el módulo completo de consentimientos.
 *
 * Crea datos simulados en consent_logs (ledger inmutable) y pending_consents
 * (portal cautivo) para probar el flujo end-to-end del Trust Widget, Portal
 * Cautivo y el verificador de integridad criptográfica.
 *
 * Seguridad: solo ejecuta en entorno local o testing. Nunca en production.
 */
class ConsentModuleTestSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->isLocal() && ! app()->environment('testing')) {
            $this->command->warn('ConsentModuleTestSeeder solo corre en entorno local.');

            return;
        }

        $company = Company::first();

        if (! $company) {
            $this->command->error('No existe ninguna empresa en la BD. Ejecutar UsersSeeder primero.');

            return;
        }

        if (! $company->integration_secret) {
            $company->integration_secret = bin2hex(random_bytes(32));
            $company->save();
        }

        $domains = $company->allowed_domains ?? [];
        if (! in_array('localhost', $domains)) {
            $domains[] = 'localhost';
            $company->allowed_domains = $domains;
            $company->save();
        }

        $policy = CompanyPolicy::where('company_id', $company->id)
            ->where('status', 'published')
            ->latest('id')
            ->first();

        if (! $policy) {
            $this->command->error('La empresa no tiene una política publicada. Crear y publicar una política primero.');

            return;
        }

        $hashService = app(ProofHashService::class);
        $policyHash = $policy->integrity_hash;

        $this->createConsentLogs($company->id, $policy->id, $policyHash, $hashService);
        $this->createPendingConsents($company->id, $policy->id);

        $this->command->info("Datos de prueba creados. Empresa UUID: {$company->public_uuid}. Usar este UUID en el widget.");
    }

    /**
     * Crea 5 registros simulados en consent_logs mezclando capture_method
     * live_widget y live_portal con proof_hash calculado criptográficamente.
     */
    private function createConsentLogs(int $companyId, int $policyId, string $policyHash, ProofHashService $hashService): void
    {
        $samples = [
            [
                'visitor_uuid' => Str::uuid(),
                'identifier' => null,
                'purposes' => ['necessary_technical' => true, 'analytics' => true, 'marketing' => false],
                'capture_method' => 'live_widget',
            ],
            [
                'visitor_uuid' => Str::uuid(),
                'identifier' => null,
                'purposes' => ['necessary_technical' => true, 'analytics' => false, 'marketing' => false],
                'capture_method' => 'live_widget',
            ],
            [
                'visitor_uuid' => Str::uuid(),
                'identifier' => 'trabajador1@empresa.cl',
                'purposes' => ['necessary_technical' => true, 'analytics' => true, 'marketing' => true],
                'capture_method' => 'live_portal',
            ],
            [
                'visitor_uuid' => Str::uuid(),
                'identifier' => 'trabajador2@empresa.cl',
                'purposes' => ['necessary_technical' => true, 'analytics' => false, 'marketing' => true],
                'capture_method' => 'live_portal',
            ],
            [
                'visitor_uuid' => Str::uuid(),
                'identifier' => null,
                'purposes' => ['necessary_technical' => true, 'analytics' => true, 'marketing' => true],
                'capture_method' => 'live_widget',
            ],
        ];

        foreach ($samples as $sample) {
            $timestamp = now()->subMinutes(rand(1, 1440))->toIso8601String();

            $payload = $hashService->buildPayload(
                $sample['identifier'] ?? $sample['visitor_uuid'],
                $policyHash,
                $sample['purposes'],
                $timestamp,
            );

            $proofHash = $hashService->compute($payload);

            ConsentLog::create([
                'visitor_uuid' => $sample['visitor_uuid'],
                'identifier' => $sample['identifier'],
                'company_id' => $companyId,
                'company_policy_id' => $policyId,
                'purposes' => $sample['purposes'],
                'policy_hash' => $policyHash,
                'proof_hash' => $proofHash,
                'consent_occurred_at' => $timestamp,
                'capture_method' => $sample['capture_method'],
                'ip_hash' => hash('sha256', '127.0.0.1'),
                'user_agent' => 'ConsentModuleTestSeeder/1.0',
            ]);
        }

        $this->command->info('  ✓ 5 registros creados en consent_logs (3 live_widget + 2 live_portal).');
    }

    /**
     * Crea 1 pending_consent en estado pending (vence en 72h) y 1 en estado
     * confirmed (firmado hace 1 día).
     */
    private function createPendingConsents(int $companyId, int $policyId): void
    {
        $pendingEmail = 'seeder-pending-'.time().'@test.cl';

        PendingConsent::create([
            'company_id' => $companyId,
            'company_policy_id' => $policyId,
            'token' => PendingConsent::generateToken(),
            'pii_payload' => ['email' => $pendingEmail],
            'pii_hash' => hash('sha256', $pendingEmail),
            'status' => 'pending',
            'source' => 'manual_panel',
            'expires_at' => now()->addHours(72),
        ]);

        $confirmedEmail = 'seeder-confirmed-'.time().'@test.cl';

        PendingConsent::create([
            'company_id' => $companyId,
            'company_policy_id' => $policyId,
            'token' => PendingConsent::generateToken(),
            'pii_payload' => ['email' => $confirmedEmail],
            'pii_hash' => hash('sha256', $confirmedEmail),
            'status' => 'confirmed',
            'source' => 'manual_panel',
            'expires_at' => now()->subDay(),
            'confirmed_at' => now()->subDay(),
        ]);

        $this->command->info('  ✓ 2 registros creados en pending_consents (1 pending + 1 confirmed).');
    }
}
