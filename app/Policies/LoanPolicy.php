<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Loan $loan): bool
    {
        return $user->id === $loan->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Loan $loan): bool
    {
        return $user->id === $loan->user_id;
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $user->id === $loan->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Loan $loan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Loan $loan): bool
    {
        return false;
    }
}
