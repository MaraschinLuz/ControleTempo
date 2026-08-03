<?php

namespace App\Policies;

use App\Models\Demand;
use App\Models\User;

class DemandPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->active;
    }

    public function view(User $user, Demand $demand): bool
    {
        return $user->isManager() || $demand->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->active;
    }

    public function update(User $user, Demand $demand): bool
    {
        return $user->isManager() || $demand->user_id === $user->id;
    }

    public function delete(User $user, Demand $demand): bool
    {
        return $this->update($user, $demand);
    }
}
