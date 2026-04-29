<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessActivity extends Model
{
    /**
     * Catálogo estático de actividades económicas del SII.
     */
    protected $table = 'business_activities';

    /**
     * Clave primaria personalizada (código SII).
     */
    protected $primaryKey = 'code';

    /**
     * La PK no es autoincremental.
     */
    public $incrementing = false;

    /**
     * La PK es un string.
     */
    protected $keyType = 'string';

    /**
     * Catálogo estático, sin timestamps.
     */
    public $timestamps = false;

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'code',
        'sector_id',
        'description',
        'subject_to_vat',
        'tax_category',
        'available_online',
    ];

    /**
     * Relación con Sector.
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }
}
