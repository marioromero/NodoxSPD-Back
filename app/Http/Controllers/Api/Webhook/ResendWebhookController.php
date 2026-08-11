<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use App\Models\PendingConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Webhook para recibir notificaciones de eventos de email de Resend.
 *
 * Resend envía webhooks a este endpoint cuando ocurren eventos como:
 * email.bounced, email.delivered, email.complained, etc.
 *
 * Seguridad:
 * - Valida la firma HMAC del header signature contra RESEND_WEBHOOK_SECRET.
 * - Sin el secrete configurado, retorna 503 (servicio no disponible).
 * - Si la firma es inválida, retorna 401 (no autorizado).
 *
 * Procesamiento de rebotes:
 * - Extrae el destinatario del payload.
 * - Busca el PendingConsent asociado con status=pending.
 * - Actualiza el estado a 'bounced'.
 *
 * Resend firma el webhook con HMAC-SHA256 sobre el body raw.
 * El header puede ser 'svix-signature' (Resend usa Svix) o 'signature'.
 */
class ResendWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('services.resend.webhook_secret');

        if (! $secret) {
            Log::warning('ResendWebhook: RESEND_WEBHOOK_SECRET no configurado.');

            return response()->json([
                'status' => false,
                'message' => 'Webhook secret not configured.',
            ], 503);
        }

        if (! $this->verifySignature($request, $secret)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid signature.',
            ], 401);
        }

        $event = $request->input('event');
        $data = $request->input('data', []);

        Log::info('ResendWebhook: evento recibido', [
            'event' => $event,
            'email' => $data['to'] ?? null,
        ]);

        if ($event === 'email.bounced') {
            $this->handleBounce($data);
        }

        return response()->json([
            'status' => true,
            'message' => 'Webhook procesado.',
        ], 200);
    }

    /**
     * Verifica la firma HMAC-SHA256 del webhook de Resend.
     *
     * Resend usa Svix como proveedor de webhooks. La firma viene en el header
     * 'svix-signature' con formato 'v1,base64hash' o 'v1,hexhash'.
     * El mensaje firmado es: "{timestamp}.{body}".
     *
     * @param  Request  $request  Request con headers y body raw.
     * @param  string  $secret  Secreto compartido (RESEND_WEBHOOK_SECRET).
     */
    private function verifySignature(Request $request, string $secret): bool
    {
        $signatureHeader = $request->header('svix-signature')
            ?? $request->header('signature');

        if (! $signatureHeader) {
            return false;
        }

        $timestamp = $request->header('svix-timestamp')
            ?? $request->header('timestamp');

        if (! $timestamp) {
            return false;
        }

        // Rechazar timestamps > 5 minutos para prevenir replay attacks
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $body = $request->getContent();
        $message = "{$timestamp}.{$body}";

        $expected = hash_hmac('sha256', $message, $secret);

        // Resend/Svix envía la firma como 'v1,{hash}'
        $parts = explode(',', $signatureHeader);
        foreach ($parts as $part) {
            if (str_starts_with($part, 'v1=')) {
                $receivedHash = substr($part, 3);

                return hash_equals($expected, $receivedHash);
            }
        }

        // Fallback: firma directa sin prefijo
        return hash_equals($expected, $signatureHeader);
    }

    /**
     * Procesa un rebote: marca el PendingConsent asociado como bounced.
     *
     * @param  array<string, mixed>  $data  Payload del evento de Resend.
     */
    private function handleBounce(array $data): void
    {
        $email = $data['to'] ?? null;

        if (! $email) {
            Log::warning('ResendWebhook: rebote sin email destinatario.', ['data' => $data]);

            return;
        }

        $piiHash = hash('sha256', $email);

        $pendingConsent = PendingConsent::where('pii_hash', $piiHash)
            ->where('status', 'pending')
            ->latest('created_at')
            ->first();

        if ($pendingConsent) {
            $pendingConsent->update(['status' => 'bounced']);

            Log::info('ResendWebhook: PendingConsent marcado como bounced', [
                'pending_consent_id' => $pendingConsent->id,
                'email' => $email,
            ]);
        } else {
            Log::info('ResendWebhook: rebote sin PendingConsent pendiente asociado', [
                'email' => $email,
            ]);
        }
    }
}
