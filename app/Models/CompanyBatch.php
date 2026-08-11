<?php

namespace App\Models;

use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tabla puente company_batches: relaciona empresas con lotes de Bus::batch.
 *
 * Aislamiento multitenant: cada empresa solo puede consultar el progreso
 * de sus propios lotes via GET /api/panel/batches/{id}.
 */
class CompanyBatch extends Model
{
    use HasFactory;

    protected $table = 'company_batches';

    protected $fillable = [
        'company_id',
        'batch_id',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id', 'id');
    }
}
