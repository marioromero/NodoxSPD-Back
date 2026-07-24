<?php

namespace App\Jobs;

use App\Mail\ConsentLinkMail;
use App\Models\PendingConsent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Job en cola para despachar el correo transaccional del Portal Cautivo.
 *
 * Recibe una instancia de PendingConsent, descifra el email del destinatario
 * desde pii_payload en memoria, construye la URL mágica del portal cautivo
 * y envía el Mailable ConsentLinkMail.
 *
 * Seguridad clave:
 * - El email nunca se lee desde BD en texto plano; se descifra en memoria.
 * - El token de la URL mágica es criptográficamente aleatorio (64 chars).
 * - El nombre de la empresa y el documento se obtienen via relaciones Eloquent.
 */
class SendConsentLinkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  PendingConsent  $pendingConsent  Registro con token, PII cifrada y relaciones.
     */
    public function __construct(
        public readonly PendingConsent $pendingConsent,
    ) {}

    /**
     * Ejecuta el envío del correo en background.
     *
     * Flujo:
     * 1. Descifra pii_payload para obtener el email del destinatario.
     * 2. Construye la URL mágica: {frontend_url}/portal/consent/{token}.
     * 3. Obtiene el nombre comercial de la empresa y el tipo de documento.
     * 4. Despacha ConsentLinkMail al destinatario.
     */
    public function handle(): void
    {
        $email = $this->pendingConsent->decrypted_pii['email'] ?? null;

        if (! $email) {
            return;
        }

        $consentUrl = config('app.frontend_url').'/portal/consent/'.$this->pendingConsent->token;

        $companyName = $this->pendingConsent->company->business_name;

        $policyType = $this->formatPolicyName(
            $this->pendingConsent->companyPolicy->document_type ?? 'documento',
        );

        $expiresAt = ConsentLinkMail::formatExpiresAt($this->pendingConsent->expires_at);

        Mail::to($email)->send(new ConsentLinkMail($companyName, $policyType, $consentUrl, $expiresAt));
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
