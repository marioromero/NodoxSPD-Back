<?php

namespace App\Jobs;

use App\Mail\ConsentLinkMail;
use App\Models\Company;
use App\Models\CompanyPolicy;
use App\Models\PendingConsent;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Job en cola para crear un PendingConsent idempotente y despachar el
 * correo transaccional del Portal Cautivo.
 *
 * Dos modos de operación:
 *
 * - Camino síncrono (single): El controlador ya creó el PendingConsent.
 *   Se pasa via $pendingConsent. El Job solo envía el correo.
 *
 * - Camino asíncrono (batch): El Job recibe parámetros planos y es
 *   responsable de la escritura idempotente en pending_consents. Si la BD
 *   arroja error de duplicado (ya existe vigente), captura la excepción
 *   amablemente y hace return sin enviar.
 *
 * TECH DEBT: El driver de cola 'database' tiene un techo de escalabilidad.
 * Cuando el volumen sostenido supere los ~5,000 jobs/hora, migrar a Redis
 * o SQS para evitar contención en la tabla jobs y soportar prioridades
 * nativas, retry-backoff distribuido y visibility windows.
 */
class SendConsentLinkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  int  $companyId  ID de la empresa propietaria del envío.
     * @param  int  $companyPolicyId  ID de la política a firmar.
     * @param  string  $email  Email plano del destinatario (se cifra en el modelo).
     * @param  string  $pendingUniquenessKey  Clave única precalculada (pii_hash:policy_id) para idempotencia.
     * @param  PendingConsent|null  $pendingConsent  Registro pre-creado (camino síncrono). Null en modo batch.
     */
    public function __construct(
        public readonly int $companyId,
        public readonly int $companyPolicyId,
        public readonly string $email,
        public readonly string $pendingUniquenessKey,
        public readonly ?PendingConsent $pendingConsent = null,
    ) {}

    /**
     * Middleware de cola: rate limiting para el envío de correos.
     *
     * Usa el limiter 'consent-emails' definido en AppServiceProvider.
     * Limita a 30 emails/min por empresa para no saturar el SMTP/Resend.
     *
     * @return array<int, RateLimited>
     */
    public function middleware(): array
    {
        return [
            new RateLimited('consent-emails'),
        ];
    }

    /**
     * Ejecuta el envío del correo en background.
     *
     * Flujo:
     * 1. Si el batch fue cancelado, abortar (solo modo batch).
     * 2. Si PendingConsent fue pre-creado (camino síncrono), saltar a envío.
     * 3. Escritura idempotente en pending_consents. Si ya existe vigente
     *    (error 1062 o equivalente SQLite), return silencioso.
     * 4. Descifra pii_payload para obtener el email del destinatario.
     * 5. Construye la URL mágica: {frontend_url}/portal/consent/{token}.
     * 6. Despacha ConsentLinkMail al destinatario.
     */
    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        // Camino síncrono: PendingConsent ya creado por el controlador
        if ($this->pendingConsent) {
            $this->sendMail($this->pendingConsent);

            return;
        }

        // Camino asíncrono (batch): crear PendingConsent idempotente
        $piiHash = hash('sha256', $this->email);

        try {
            $pendingConsent = PendingConsent::create([
                'company_id' => $this->companyId,
                'company_policy_id' => $this->companyPolicyId,
                'token' => PendingConsent::generateToken(),
                'pii_payload' => ['email' => $this->email],
                'pii_hash' => $piiHash,
                'status' => 'pending',
                'source' => 'manual_panel',
                'expires_at' => now()->addDays(7),
            ]);
        } catch (QueryException $e) {
            $errorCode = $e->errorInfo[1] ?? null;

            if ($errorCode === 1062 || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                return;
            }

            throw $e;
        }

        $this->sendMail($pendingConsent);
    }

    /**
     * Despacha ConsentLinkMail al destinatario del PendingConsent.
     *
     * @param  PendingConsent  $pendingConsent  Registro con token, PII cifrada y relaciones.
     */
    private function sendMail(PendingConsent $pendingConsent): void
    {
        $email = $pendingConsent->decrypted_pii['email'] ?? null;

        if (! $email) {
            return;
        }

        $consentUrl = config('app.frontend_url').'/portal/consent/'.$pendingConsent->token;

        $company = Company::find($pendingConsent->company_id);
        $policy = CompanyPolicy::find($pendingConsent->company_policy_id);

        $companyName = $company?->business_name ?? 'NodoxSPD';

        $policyType = $this->formatPolicyName(
            $policy->document_type ?? 'documento',
        );

        $expiresAt = ConsentLinkMail::formatExpiresAt($pendingConsent->expires_at);

        Mail::to($email)->send(new ConsentLinkMail($companyName, $policyType, $consentUrl, $expiresAt));
    }

    /**
     * Determina los intentos de retry del Job.
     *
     * @return array<int, mixed>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    /**
     * Convierte el document_type técnico a un nombre legible para el correo.
     *
     * @param  string  $documentType  Tipo técnico (ej: cookie_policy, workers_policy).
     * @return string Nombre legible (ej: "Política de Cookies", "Política de Trabajadores").
     */
    private function formatPolicyName(string $documentType): string
    {
        return match ($documentType) {
            'cookie_policy' => 'Política de Cookies',
            'privacy_policy' => 'Política de Privacidad',
            'workers_policy' => 'Política de Trabajadores',
            default => 'Documento Legal',
        };
    }
}
