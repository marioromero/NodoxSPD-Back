<?php

namespace App\Services\Consent;

use App\Models\ConsentLog;

class ProofHashService
{
    /**
     * Construye el payload normalizado del consentimiento a partir de los campos crudos.
     * Este array es la entrada base para la canonización y posterior hash de prueba (proof hash).
     *
     * @return array{identifier: string, purposes: array, policy_hash: string, timestamp: string}
     */
    public function buildPayload(string $identifier, string $policyHash, array $purposes, string $timestamp): array
    {
        return [
            'identifier' => $identifier,
            'purposes' => $purposes,
            'policy_hash' => $policyHash,
            'timestamp' => $timestamp,
        ];
    }

    /**
     * Canoniza el payload ordenando las llaves alfabéticamente de forma recursiva
     * (incluyendo sub-arrays como purposes) y lo serializa como JSON compacto.
     *
     * El resultado es determinista: mismas entradas siempre producen el mismo string,
     * independientemente del orden en que se hayan proporcionado los datos.
     */
    public function canonicalize(array $payload): string
    {
        $this->ksortRecursive($payload);

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Calcula el hash SHA-256 (hexadecimal minúsculas, 64 caracteres) del payload canonizado.
     * Este hash se almacena como proof_hash en consent_logs y sirve como evidancia criptográfica
     * de que el consentimiento no ha sido alterado.
     */
    public function compute(array $payload): string
    {
        return hash('sha256', $this->canonicalize($payload));
    }

    /**
     * Recalcula el proof_hash a partir de un modelo ConsentLog almacenado.
     * Se usa para verificar la integridad del registro: si el hash recalculado
     * no coincide con el almacenado, el registro fue alterado.
     */
    public function computeForLog(ConsentLog $log): string
    {
        $payload = $this->buildPayload(
            $log->identifier,
            $log->policy_hash,
            $log->purposes,
            $log->consent_occurred_at->toIso8601String(),
        );

        return $this->compute($payload);
    }

    /**
     * Ordena recursivamente las llaves de un array usando ksort().
     * Asegura que el payload produza un JSON canónico determinista
     * incluso cuando contiene sub-arrays (ej: purposes).
     */
    private function ksortRecursive(array &$array): void
    {
        ksort($array);

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }
}
