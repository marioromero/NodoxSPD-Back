<?php

namespace App\Policies;

use App\Models\User;

class TriageQuestionPolicy
{
    /**
     * Determine if the user can view any triage questions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('company_admin');
    }
}
