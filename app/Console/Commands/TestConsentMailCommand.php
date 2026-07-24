<?php

namespace App\Console\Commands;

use App\Jobs\SendConsentLinkJob;
use App\Mail\ConsentLinkMail;
use App\Models\Company;
use App\Models\CompanyPolicy;
use App\Models\PendingConsent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Comando de prueba para el flujo de correos transaccionales del Portal Cautivo.
 *
 * Busca un PendingConsent pendiente vigente o crea uno temporal, y despacha
 * el Job de envío de correo de forma sincrónica (dispatchSync) para evitar
 * depender del queue worker durante las pruebas.
 *
 * Renderiza el HTML del correo en consola para inspección visual inmediata,
 * además de ejecutar el envío real via el mailer configurado.
 */
#[Signature('consent:test-mail {email}')]
#[Description('Prueba el flujo de envío de correo del Portal Cautivo')]
class TestConsentMailCommand extends Command
{
    public function handle(): int
    {
        $email = $this->argument('email');

        $pendingConsent = PendingConsent::where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $pendingConsent) {
            $company = Company::first();
            $policy = CompanyPolicy::where('company_id', $company->id)
                ->where('status', 'published')
                ->latest('id')
                ->first();

            if (! $company || ! $policy) {
                $this->error('No hay empresa o política publicada disponible para crear un registro de prueba.');

                return self::FAILURE;
            }

            $pendingConsent = PendingConsent::create([
                'company_id' => $company->id,
                'company_policy_id' => $policy->id,
                'token' => PendingConsent::generateToken(),
                'pii_payload' => ['email' => $email],
                'pii_hash' => hash('sha256', $email),
                'status' => 'pending',
                'source' => 'manual_panel',
                'expires_at' => now()->addDays(7),
            ]);

            $this->info("Registro de prueba creado (id={$pendingConsent->id}).");
        }

        $this->info('Destinatario PII: '.$pendingConsent->decrypted_pii['email']);
        $this->info('Token: '.$pendingConsent->token);
        $this->info('Mailer: '.config('mail.default'));

        SendConsentLinkJob::dispatchSync($pendingConsent);

        $this->info("\n--- HTML del correo renderizado ---\n");

        $mail = new ConsentLinkMail(
            $pendingConsent->company->business_name,
            $this->formatPolicyName($pendingConsent->companyPolicy->document_type ?? 'documento'),
            config('app.frontend_url').'/portal/consent/'.$pendingConsent->token,
            ConsentLinkMail::formatExpiresAt($pendingConsent->expires_at),
        );

        $this->line($mail->render());

        $this->info("\n--- Fin del render ---");
        $this->info("Email procesado para {$email}.");
        $this->info('Revisar storage/logs/ si MAIL_MAILER=log');

        return self::SUCCESS;
    }

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
