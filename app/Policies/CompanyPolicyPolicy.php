<?php

namespace App\Policies;

use App\Models\CompanyPolicy;
use App\Models\User;

class CompanyPolicyPolicy
{
    protected function belongsToCompany(User $user, CompanyPolicy $policy): bool
    {
        return (int) $user->company?->id === (int) $policy->company_id;
    }

    public function update(User $user, CompanyPolicy $policy): bool
    {
        return $this->belongsToCompany($user, $policy) && $policy->status === 'draft';
    }

    public function archive(User $user, CompanyPolicy $policy): bool
    {
        return $this->belongsToCompany($user, $policy) && $policy->status === 'published';
    }

    public function view(User $user, CompanyPolicy $policy): bool
    {
        return $this->belongsToCompany($user, $policy);
    }

    public function publish(User $user, CompanyPolicy $policy): bool
    {
        return $this->belongsToCompany($user, $policy) && $policy->status === 'draft';
    }

    public function delete(User $user, CompanyPolicy $policy): bool
    {
        return $this->belongsToCompany($user, $policy) && $policy->status === 'archived';
    }
}
