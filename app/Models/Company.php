<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    protected $fillable = [
        'user_id',
        'public_uuid',
        'business_name',
        'tax_id',
        'industry_type',
        'legal_address',
        'is_foreign_entity',
        'dpo_designation_act',
        'dpo_contact',
        'legal_settings',
        'last_impact_assessment_at',
        'security_policy_version',
    ];

    protected $casts = [
        'is_foreign_entity' => 'boolean',
        'dpo_contact' => 'array',
        'legal_settings' => 'array',
        'last_impact_assessment_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
