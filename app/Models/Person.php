<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Person extends Model
{
    protected $fillable = [
        'user_id',
        'guardian_id',
        'first_name',
        'last_name',
        'tax_id',
        'phone',
        'birth_date',
        'consent_logs',
        'sensitive_data_categories',
        'is_blocked',
        'retention_expiry_at',
        'last_notified_breach_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'consent_logs' => 'array',
        'sensitive_data_categories' => 'array',
        'is_blocked' => 'boolean',
        'retention_expiry_at' => 'datetime',
        'last_notified_breach_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(self::class, 'guardian_id');
    }

    public function ward(): HasOne
    {
        return $this->hasOne(self::class, 'guardian_id');
    }
}
