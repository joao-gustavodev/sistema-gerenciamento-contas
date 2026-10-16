<?php

namespace App\Policies;

use App\Models\FinancialAccount;
use App\Models\User;

class FinancialAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FinancialAccount $account): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canEditAccounts();
    }

    public function update(User $user, FinancialAccount $account): bool
    {
        return $user->canEditAccounts();
    }

    public function createTransaction(User $user, FinancialAccount $account): bool
    {
        return $user->canEditAccounts();
    }

    public function reverseTransaction(User $user, FinancialAccount $account): bool
    {
        return $user->canEditAccounts();
    }
}
