<?php

namespace App\Policies;

use App\Models\MoneyDashboardPanel;
use App\Models\User;

class MoneyDashboardPanelPolicy
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
    public function view(User $user, MoneyDashboardPanel $moneyDashboardPanel): bool
    {
        return (string) $user->id === $moneyDashboardPanel->dashboard()->first()->user_id;
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
    public function update(User $user, MoneyDashboardPanel $moneyDashboardPanel): bool
    {
        return (string) $user->id === $moneyDashboardPanel->dashboard()->first()->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MoneyDashboardPanel $moneyDashboardPanel): bool
    {
        return (string) $user->id === $moneyDashboardPanel->dashboard()->first()->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MoneyDashboardPanel $moneyDashboardPanel): bool
    {
        return (string) $user->id === $moneyDashboardPanel->dashboard()->first()->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MoneyDashboardPanel $moneyDashboardPanel): bool
    {
        return (string) $user->id === $moneyDashboardPanel->dashboard()->first()->user_id;
    }
}
