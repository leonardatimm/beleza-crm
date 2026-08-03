<?php

namespace App\Observers;

use App\Models\Membership;
use App\Models\ProfessionalAvailability;
use App\Models\ProfessionalProfile;

class MembershipObserver
{
    public function created(Membership $membership): void
    {
        $profile = ProfessionalProfile::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $membership->tenant_id, 'user_id' => $membership->user_id],
            ['display_name' => $membership->user()->value('name') ?? 'Profissional'],
        );

        foreach (range(1, 5) as $weekday) {
            ProfessionalAvailability::withoutGlobalScopes()->firstOrCreate([
                'tenant_id' => $membership->tenant_id,
                'professional_profile_id' => $profile->id,
                'weekday' => $weekday,
                'starts_at' => '09:00:00',
                'ends_at' => '18:00:00',
            ]);
        }
    }

    public function deleted(Membership $membership): void
    {
        ProfessionalProfile::withoutGlobalScopes()
            ->where('tenant_id', $membership->tenant_id)
            ->where('user_id', $membership->user_id)
            ->update(['is_active' => false]);
    }
}
