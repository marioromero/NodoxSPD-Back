<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class ConsentLog extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'purposes' => 'array',
        'validation_errors' => 'array',
        'consent_occurred_at' => 'datetime',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
    ];

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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function companyPolicy(): BelongsTo
    {
        return $this->belongsTo(CompanyPolicy::class);
    }
}
