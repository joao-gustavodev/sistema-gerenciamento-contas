<?php

namespace App\Policies;

use App\Models\Revenue;
use App\Models\User;

class RevenuePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Revenue $revenue): bool
    {
        return (int) $revenue->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->canEditAccounts();
    }

    public function update(User $user, Revenue $revenue): bool
    {
        return $this->ownsAndCanEdit($user, $revenue);
    }

    public function delete(User $user, Revenue $revenue): bool
    {
        return $this->ownsAndCanEdit($user, $revenue);
    }

    private function ownsAndCanEdit(User $user, Revenue $revenue): bool
    {
        return (int) $revenue->user_id === (int) $user->id && $user->canEditAccounts();
    }
}
