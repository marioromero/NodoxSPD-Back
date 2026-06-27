<?php

namespace App\Services\Consent;

use App\Models\ConsentLog;

class ProofHashService
{
    public function buildPayload(string $identifier, string $policyHash, array $purposes, string $timestamp): array
    {
        return [
            'identifier' => $identifier,
            'purposes' => $purposes,
            'policy_hash' => $policyHash,
            'timestamp' => $timestamp,
        ];
    }

    public function canonicalize(array $payload): string
    {
        $this->ksortRecursive($payload);

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function compute(array $payload): string
    {
        return hash('sha256', $this->canonicalize($payload));
    }

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
