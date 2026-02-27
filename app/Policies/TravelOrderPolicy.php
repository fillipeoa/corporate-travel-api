<?php

namespace App\Policies;

use App\Models\TravelOrder;
use App\Models\User;

class TravelOrderPolicy
{
    /**
     * Determine whether the user can view the travel order.
     * Only the owner or an admin can view.
     */
    public function view(User $user, TravelOrder $travelOrder): bool
    {
        return $user->id === $travelOrder->user_id || $user->is_admin;
    }

    /**
     * Determine whether the user can create travel orders.
     * Any authenticated user can create.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the status.
     * Only admins can update status, and the admin cannot be the requester.
     */
    public function updateStatus(User $user, TravelOrder $travelOrder): bool
    {
        return $user->is_admin && $user->id !== $travelOrder->user_id;
    }
}
