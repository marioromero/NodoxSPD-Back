<?php

namespace App\Policies;

use App\Models\CompanyPolicy;
use App\Models\User;

class CompanyPolicyPolicy
{
    /**
     * Determina si el usuario puede actualizar la política.
     */
    public function update(User $user, CompanyPolicy $policy): bool
    {
        $belongsToCompany = $user->company->id === $policy->company_id;
        $isDraft = $policy->status === 'draft';

        return $belongsToCompany && $isDraft;
    }

    /**
     * Determina si el usuario puede archivar la política.
     */
    public function archive(User $user, CompanyPolicy $policy): bool
    {
        $belongsToCompany = $user->company->id === $policy->company_id;
        $isPublished = $policy->status === 'published';

        return $belongsToCompany && $isPublished;
    }

    /**
     * Determina si el usuario puede ver la política.
     */
    public function view(User $user, CompanyPolicy $policy): bool
    {
        return $user->company->id === $policy->company_id;
    }

    /**
     * Determina si el usuario puede publicar la política.
     */
    public function publish(User $user, CompanyPolicy $policy): bool
    {
        $belongsToCompany = $user->company->id === $policy->company_id;
        $isDraft = $policy->status === 'draft';

        return $belongsToCompany && $isDraft;
    }

    /**
     * Determina si el usuario puede eliminar la política.
     */
    public function delete(User $user, CompanyPolicy $policy): bool
    {
        $belongsToCompany = $user->company->id === $policy->company_id;
        $isArchived = $policy->status === 'archived';

        return $belongsToCompany && $isArchived;
    }
}
