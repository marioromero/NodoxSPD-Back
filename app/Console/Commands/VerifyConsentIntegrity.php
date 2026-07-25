<?php

namespace App\Console\Commands;

use App\Models\ConsentLog;
use App\Services\Consent\ProofHashService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Verifica la integridad criptográfica del ledger inmutable de consentimientos.
 *
 * Recalcula el proof_hash de cada registro en consent_logs usando ProofHashService
 * y lo compara con el hash almacenado. Si no coinciden, el registro fue adulterado.
 *
 * El ledger es inmutable: no se puede corregir automáticamente. La flag --fix
 * solo imprime una advertencia explicando el procedimiento manual requerido.
 */
#[Signature('consent:verify-integrity {--company= : Filtrar por company_id} {--limit=100 : Número máximo de registros a verificar} {--fix : Mostrar instrucciones de corrección manual}')]
#[Description('Verifica la integridad criptográfica de consent_logs recalculando proof_hash')]
class VerifyConsentIntegrity extends Command
{
    public function handle(ProofHashService $hashService): int
    {
        $query = ConsentLog::query()->latest('id');

        if ($this->option('company')) {
            $query->where('company_id', (int) $this->option('company'));
        }

        $logs = $query->limit((int) $this->option('limit'))->get();

        if ($logs->isEmpty()) {
            $this->info('No hay registros de consentimiento para verificar.');

            return self::SUCCESS;
        }

        $this->info("Verificando {$logs->count()} registro(s) de consent_logs...");
        $this->newLine();

        $adulterated = 0;

        foreach ($logs as $log) {
            $expectedHash = $hashService->computeForLog($log);
            $storedHash = $log->proof_hash;

            if (hash_equals($expectedHash, $storedHash)) {
                $this->info("✓ ID {$log->id} — íntegro");
            } else {
                $this->error("✗ ID {$log->id} — ADULTERADO. Hash esperado: {$expectedHash}. Hash almacenado: {$storedHash}");
                $adulterated++;

                if ($this->option('fix')) {
                    $this->warn('  El ledger es inmutable. No se puede corregir automáticamente. Contactar al administrador.');
                }
            }
        }

        $this->newLine();

        if ($adulterated > 0) {
            $this->error("Verificación completada: {$adulterated} registro(s) adulterado(s) de {$logs->count()} verificado(s).");

            return self::FAILURE;
        }

        $this->info("Verificación completada: todos los {$logs->count()} registro(s) están íntegros.");

        return self::SUCCESS;
    }
}
