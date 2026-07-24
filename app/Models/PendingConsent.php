<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * Modelo para tokens de consentimiento pendientes (Portal Cautivo).
 *
 * Representa una solicitud de firma de política enviada a un destinatario
 * por canal asíncrono (email). Los datos personales del destinatario (PII)
 * se almacenan cifrados en pii_payload usando AES-256-CBC via Crypt facade,
 * cumpliendo el principio de Privacy by Design de la Ley 21.719.
 *
 * El token es un hash criptográfico aleatorio (64 chars hex) que se embebe
 * en la URL mágica del portal cautivo. El índice único parcial
 * pending_uniqueness_key previene duplicados pendientes para el mismo
 * destinatario + política.
 */
class PendingConsent extends Model
{
    /** @var array<string> Atributos asignables masivamente. */
    protected $fillable = [
        'company_id',
        'company_policy_id',
        'token',
        'pii_payload',
        'pii_hash',
        'status',
        'source',
        'expires_at',
        'confirmed_at',
    ];

    /** @var array<string, string> Casts para forzar tipos nativos. */
    protected $casts = [
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    /**
     * Mutador: cifra el payload PII antes de persistirlo en BD.
     *
     * Acepta un array plano (ej: ['email' => 'juan@example.com', 'name' => 'Juan'])
     * y lo serializa + cifra usando AES-256-CBC. El valor en BD nunca es legible.
     *
     * @param  array<string, mixed>  $value  Datos PII en texto plano.
     */
    public function setPiiPayloadAttribute(array $value): void
    {
        $this->attributes['pii_payload'] = Crypt::encryptString(json_encode($value));
    }

    /**
     * Accesor: descifra el payload PII desde BD y lo retorna como array.
     *
     * La desencriptación ocurre en memoria, nunca toca la BD en texto plano.
     * Usado por SendConsentLinkJob para extraer el email del destinatario.
     *
     * @return array<string, mixed> Datos PII descifrados.
     */
    public function getDecryptedPiiAttribute(): array
    {
        return json_decode(Crypt::decryptString($this->attributes['pii_payload']), true);
    }

    /**
     * Genera un token criptográfico seguro de 64 caracteres hexadecimales.
     * Se usa como identificador público en la URL mágica del portal cautivo.
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /** Relación: este consentimiento pendiente pertenece a una empresa. */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Relación: este consentimiento pendiente está vinculado a una política específica. */
    public function companyPolicy(): BelongsTo
    {
        return $this->belongsTo(CompanyPolicy::class);
    }
}
