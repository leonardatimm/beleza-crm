<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use App\Policies\Concerns\AuthorizesTenantRoles;

class ServicePolicy
{
    use AuthorizesTenantRoles;

    public function viewAny(User $user): bool
    {
        return $user->current_team_id && $this->isMember($user, $user->current_team_id);
    }

    public function view(User $user, Service $service): bool
    {
        return $this->isMember($user, $service->tenant_id);
    }

    public function create(User $user): bool
    {
        return $user->current_team_id && $this->isManager($user, $user->current_team_id);
    }

    public function update(User $user, Service $service): bool
    {
        return $this->isManager($user, $service->tenant_id);
    }

    public function delete(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }
}
