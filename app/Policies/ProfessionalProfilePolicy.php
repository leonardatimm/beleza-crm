<?php

namespace App\Policies;

use App\Models\ProfessionalProfile;
use App\Models\User;
use App\Policies\Concerns\AuthorizesTenantRoles;

class ProfessionalProfilePolicy
{
    use AuthorizesTenantRoles;

    public function viewAny(User $user): bool
    {
        return $user->current_team_id && $this->isMember($user, $user->current_team_id);
    }

    public function view(User $user, ProfessionalProfile $profile): bool
    {
        return $this->isMember($user, $profile->tenant_id);
    }

    public function update(User $user, ProfessionalProfile $profile): bool
    {
        return $this->isManager($user, $profile->tenant_id) || $profile->user_id === $user->id;
    }
}
