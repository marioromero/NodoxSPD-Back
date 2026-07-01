<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Modelo Eloquent para los registros de consentimiento inmutables.
 *
 * Representa evidancia criptográfica de consentimiento Ley 21.719.
 * Una vez creado, un ConsentLog no puede ser modificado ni eliminado;
 * cualquier intento lanza LogicException (capturado como HTTP 409).
 */
class ConsentLog extends Model
{
    /** @var bool Deshabilita timestamps automáticos de Eloquent (la tabla no usa updated_at). */
    public $timestamps = false;

    /** @var array<string> Solo la llave primaria está protegida contra asignación masiva. */
    protected $guarded = ['id'];

    /** @var array<string, string> Casts para campos JSON y timestamps manuales. */
    protected $casts = [
        'purposes' => 'array',
        'validation_errors' => 'array',
        'consent_occurred_at' => 'datetime',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Hooks del ciclo de vida del modelo que implementan la inmutabilidad estricta.
     *
     * - creating: Auto-asigna recorded_at y created_at al momento de la inserción.
     * - updating: Bloquea cualquier intento de modificación (Lanzza LogicException).
     * - deleting: Bloquea cualquier intento de eliminación (Lanza LogicException).
     */
    protected static function booted(): void
    {
        static::creating(function (ConsentLog $log): void {
            $log->recorded_at = now();
            $log->created_at = now();
        });

        static::updating(function (ConsentLog $log): never {
            throw new LogicException('ConsentLog es inmutable. Operación abortada.');
        });

        static::deleting(function (ConsentLog $log): never {
            throw new LogicException('ConsentLog no puede eliminarse. Operación abortada.');
        });
    }

    /** Relación: este consentimiento pertenece a una empresa. */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Relación: este consentimiento está vinculado a la política vigente al momento del consentimiento. */
    public function companyPolicy(): BelongsTo
    {
        return $this->belongsTo(CompanyPolicy::class);
    }
}
