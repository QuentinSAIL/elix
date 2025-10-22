<?php

namespace App\Policies;

use App\Models\MoneyCategory;
use App\Models\User;

class MoneyCategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MoneyCategory $moneyCategory): bool
    {
        return $user->id === $moneyCategory->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MoneyCategory $moneyCategory): bool
    {
        return $user->id === $moneyCategory->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MoneyCategory $moneyCategory): bool
    {
        return $user->id === $moneyCategory->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MoneyCategory $moneyCategory): bool
    {
        return $user->id === $moneyCategory->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MoneyCategory $moneyCategory): bool
    {
        return $user->id === $moneyCategory->user_id;
    }
}
