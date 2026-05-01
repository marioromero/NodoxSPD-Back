<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyPolicy extends Model
{
    protected $fillable = [
        'company_id',
        'legal_template_id',
        'document_type',
        'company_version',
        'wizard_data',
        'integrity_hash',
        'status',
        'published_at',
    ];

    protected $casts = [
        'wizard_data' => 'array',
        'published_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(LegalTemplate::class, 'legal_template_id');
    }
}
