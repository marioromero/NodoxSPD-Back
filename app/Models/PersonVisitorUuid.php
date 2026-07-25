<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vincula un visitor_uuid anónimo del Trust Widget con una persona real
 * identificada por la empresa via user_ref.
 *
 * Esta tabla pivote es la única estructura que se modifica durante el
 * Identity Stitching. El ledger inmutable (consent_logs) nunca se altera.
 *
 * La deduplicación se basa en el unique key (external_ref_hash, visitor_uuid),
 * lo que garantiza que un mismo usuario externo no pueda acumular múltiples
 * visitor_uuids duplicados para la misma empresa.
 */
class PersonVisitorUuid extends Model
{
    protected $fillable = [
        'company_id',
        'person_id',
        'external_ref_hash',
        'visitor_uuid',
    ];

    protected $casts = [
        'person_id' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
