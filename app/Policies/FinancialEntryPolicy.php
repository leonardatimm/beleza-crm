<?php

namespace App\Policies;

use App\Models\FinancialEntry;
use App\Models\User;
use App\Policies\Concerns\AuthorizesTenantRoles;

class FinancialEntryPolicy
{
    use AuthorizesTenantRoles;

    public function viewAny(User $user): bool
    {
        return $user->current_team_id && $this->isManager($user, $user->current_team_id);
    }

    public function view(User $user, FinancialEntry $entry): bool
    {
        return $this->isManager($user, $entry->tenant_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, FinancialEntry $entry): bool
    {
        return $this->view($user, $entry);
    }
}
