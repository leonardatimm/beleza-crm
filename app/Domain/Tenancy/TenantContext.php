<?php

namespace App\Domain\Tenancy;

use App\Models\Team;
use App\Models\User;
use LogicException;

class TenantContext
{
    private ?Team $tenant = null;

    public function set(Team $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }

    public function setFromUser(User $user): bool
    {
        $tenant = $user->currentTeam;

        if (! $tenant || ! $user->belongsToTeam($tenant)) {
            return false;
        }

        $this->set($tenant);

        return true;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function tenant(): Team
    {
        return $this->tenant ?? throw new LogicException('Nenhum estabelecimento ativo foi definido.');
    }

    public function id(): int
    {
        return $this->tenant()->getKey();
    }
}
