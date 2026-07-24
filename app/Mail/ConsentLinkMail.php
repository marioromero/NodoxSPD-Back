<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Mailable transaccional para el Portal Cautivo.
 *
 * Despacha el correo con la URL mágica de firma al destinatario.
 * Contiene el nombre de la empresa, el tipo de documento, el enlace
 * al portal cautivo y la fecha de expiración formateada en español.
 *
 * Seguridad clave:
 * - El token no se expone como texto en el cuerpo, solo embebido en la URL.
 * - Implementa ShouldQueue para enviarse en background via colas de Laravel.
 */
class ConsentLinkMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $companyName  Nombre comercial de la empresa que solicita la firma.
     * @param  string  $policyType  Label legible del document_type (ej: "Política de Privacidad").
     * @param  string  $consentUrl  URL completa del portal cautivo con el token embebido.
     * @param  Carbon  $expiresAt  Fecha de expiración del enlace (se formatea a español en el constructor).
     */
    public function __construct(
        public readonly string $companyName,
        public readonly string $policyType,
        public readonly string $consentUrl,
        public readonly string $expiresAt,
    ) {}

    /**
     * Define el asunto del correo transaccional.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Firma requerida: {$this->companyName} solicita tu consentimiento",
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

    /**
     * Formatea una fecha Carbon a string legible en español.
     * Ej: "26 de julio de 2026 a las 14:32".
     */
    public static function formatExpiresAt(Carbon $date): string
    {
        return $date->locale('es')->translatedFormat('j \d\e F \d\e Y \a \l\a\s H:i');
    }
}
