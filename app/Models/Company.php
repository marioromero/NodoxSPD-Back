<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    protected $fillable = [
        'user_id',
        'public_uuid',
        'business_name',
        'tax_id',
        'legal_address',
        'arco_contact_email',
        'legal_representative_name',
        'legal_representative_tax_id',
        'is_foreign_entity',
        'local_contact_for_foreign_entity',
        'dpo_designation_act',
        'dpo_contact',
        'legal_settings',
        'allowed_domains',
        'integration_secret',
        'widget_config',
        'last_impact_assessment_at',
        'security_policy_version',
        'onboarding_completed_at',
    ];

    protected $casts = [
        'is_foreign_entity' => 'boolean',
        'local_contact_for_foreign_entity' => 'array',
        'dpo_contact' => 'array',
        'legal_settings' => 'array',
        'allowed_domains' => 'array',
        'widget_config' => 'array',
        'last_impact_assessment_at' => 'datetime',
        'onboarding_completed_at' => 'datetime',
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

    public function sectors(): BelongsToMany
    {
        return $this->belongsToMany(Sector::class)->withTimestamps();
    }

    public function hasCompletedOnboarding(): bool
    {
        return ! is_null($this->onboarding_completed_at);
    }
}
