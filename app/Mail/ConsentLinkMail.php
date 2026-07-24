<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable transaccional para el Portal Cautivo.
 *
 * Despacha el correo con la URL mágica de firma al destinatario.
 * Contiene el nombre de la empresa, el nombre del documento y el enlace
 * al portal cautivo donde el destinatario leerá y firmará la política.
 *
 * Implementa ShouldQueue para enviarse en background via colas de Laravel.
 */
class ConsentLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $businessName  Nombre comercial de la empresa que solicita la firma.
     * @param  string  $policyName  Nombre legible del documento a firmar.
     * @param  string  $magicUrl  URL mágica del portal cautivo con el token.
     */
    public function __construct(
        public readonly string $businessName,
        public readonly string $policyName,
        public readonly string $magicUrl,
    ) {}

    /**
     * Define el asunto del correo transaccional.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->businessName} requiere tu firma — {$this->policyName}",
        );
    }

    /**
     * Define la vista Blade que renderiza el cuerpo del correo.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.consent-link',
        );
    }
}
