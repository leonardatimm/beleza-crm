<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Policies\Concerns\AuthorizesTenantRoles;

class CustomerPolicy
{
    use AuthorizesTenantRoles;

    public function viewAny(User $user): bool
    {
        return $user->current_team_id && $this->isMember($user, $user->current_team_id);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->isMember($user, $customer->tenant_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->view($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->view($user, $customer);
    }
}
