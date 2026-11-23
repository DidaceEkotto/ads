<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function view(User $user, Site $site): bool
    {
        return $site->publisher_id === $user->id || $user->isAdmin();
    }

    public function update(User $user, Site $site): bool
    {
        return $site->publisher_id === $user->id;
    }
}
