<?php

use App\Services\Consent\ProofHashService;

/*
 * Vector fijo de prueba para el test de regresión de ProofHashService.
 * Cualquier cambio en el algoritmo de canonización o hash debe hacer fallar este test.
 */
const EXPECTED_CANONICAL_JSON = '{"identifier":"f47ac10b-58cc-4372-a567-0e02b2c3d479","policy_hash":"abc123","purposes":{"analytics":true,"marketing":false,"necessary":true},"timestamp":"2026-01-01T00:00:00Z"}';
const EXPECTED_SHA256 = '5a0ffa7f297b9a83b82a6641486d9a90af142bfa8bc31e6587141e0cd812bd3c';

/*
 * Test: buildPayload debe retornar un array con las 4 claves del contrato.
 */
test('buildPayload returns array with correct keys', function () {
    $service = new ProofHashService;

    $payload = $service->buildPayload(
        'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        'abc123',
        ['necessary' => true, 'marketing' => false, 'analytics' => true],
        '2026-01-01T00:00:00Z',
    );

    expect($payload)->toHaveKeys(['identifier', 'purposes', 'policy_hash', 'timestamp']);
});

/*
 * Test: canonicalize debe producir el JSON canónico exacto (llaves ordenadas, sin espacios).
 */
test('canonicalize produces expected JSON', function () {
    $service = new ProofHashService;

    $payload = $service->buildPayload(
        'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        'abc123',
        ['necessary' => true, 'marketing' => false, 'analytics' => true],
        '2026-01-01T00:00:00Z',
    );

    $canonical = $service->canonicalize($payload);

    expect($canonical)->toBe(EXPECTED_CANONICAL_JSON);
});

/*
 * Test: compute debe producir el SHA-256 exacto del JSON canónico esperado.
 */
test('compute produces expected SHA-256 hash', function () {
    $service = new ProofHashService;

    $payload = $service->buildPayload(
        'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        'abc123',
        ['necessary' => true, 'marketing' => false, 'analytics' => true],
        '2026-01-01T00:00:00Z',
    );

    $hash = $service->compute($payload);

    expect($hash)->toBe(EXPECTED_SHA256);
});

/*
 * Test de integración: verifica el contrato completo (canonización + hash) en una sola pasada.
 */
test('full integration matches contract', function () {
    $service = new ProofHashService;

    $payload = $service->buildPayload(
        'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        'abc123',
        ['necessary' => true, 'marketing' => false, 'analytics' => true],
        '2026-01-01T00:00:00Z',
    );

    expect($service->canonicalize($payload))->toBe(EXPECTED_CANONICAL_JSON);
    expect($service->compute($payload))->toBe(EXPECTED_SHA256);
});
