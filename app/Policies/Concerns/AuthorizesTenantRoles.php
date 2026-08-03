<?php

namespace App\Policies\Concerns;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

trait AuthorizesTenantRoles
{
    private function role(User $user, int $tenantId): ?TeamRole
    {
        return $user->teamRole(Team::query()->findOrFail($tenantId));
    }

    private function isManager(User $user, int $tenantId): bool
    {
        return in_array($this->role($user, $tenantId), [TeamRole::Owner, TeamRole::Manager, TeamRole::Admin], true);
    }

    private function isMember(User $user, int $tenantId): bool
    {
        return $this->role($user, $tenantId) !== null;
    }
}
