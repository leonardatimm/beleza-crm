<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\ProfessionalProfile;
use App\Models\User;
use App\Policies\Concerns\AuthorizesTenantRoles;

class AppointmentPolicy
{
    use AuthorizesTenantRoles;

    public function viewAny(User $user): bool
    {
        return $user->current_team_id && $this->isMember($user, $user->current_team_id);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($this->isManager($user, $appointment->tenant_id)) {
            return true;
        }

        return ProfessionalProfile::withoutGlobalScopes()
            ->where('tenant_id', $appointment->tenant_id)
            ->where('user_id', $user->id)
            ->whereKey($appointment->professional_profile_id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }

    public function complete(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }
}
