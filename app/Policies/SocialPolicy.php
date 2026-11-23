<?php

namespace App\Policies;

use App\Models\SocialAccount;
use App\Models\SocialPlacement;
use App\Models\User;

class SocialPolicy
{
    public function manageAccount(User $user, SocialAccount $account): bool
    {
        return $account->publisher_id === $user->id;
    }

    public function managePlacement(User $user, SocialPlacement $placement): bool
    {
        return $placement->publisher_id === $user->id;
    }
}
