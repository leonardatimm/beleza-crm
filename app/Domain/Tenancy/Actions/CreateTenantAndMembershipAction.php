<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Data\CreateTenantData;
use App\Domain\Tenancy\Enums\BusinessType;
use App\Domain\Tenancy\Enums\DocumentType;
use App\Enums\TeamRole;
use App\Models\Membership;
use App\Models\Tenant;
use App\Models\User;
use App\Support\BrazilianDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CreateTenantAndMembershipAction
{
    public function handle(User $user, CreateTenantData $data): Tenant
    {
        $tenant = DB::transaction(function () use ($user, $data): Tenant {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUser->current_team_id !== null) {
                throw ValidationException::withMessages([
                    'business_type' => 'Este usuário já concluiu o cadastro do estabelecimento.',
                ]);
            }

            $isIndividual = $data->businessType === BusinessType::Individual;
            $name = $isIndividual ? $data->professionalName : $data->tradeName;

            $tenant = Tenant::query()->create([
                'name' => $name,
                'is_personal' => $isIndividual,
                'business_type' => $data->businessType->value,
                'document_type' => $isIndividual ? DocumentType::Cpf->value : DocumentType::Cnpj->value,
                'document_number' => BrazilianDocument::digits($isIndividual ? $data->cpf : $data->cnpj),
                'legal_name' => $isIndividual ? null : $data->legalName,
                'trade_name' => $isIndividual ? $data->professionalName : $data->tradeName,
                'phone' => BrazilianDocument::digits($data->phone),
                'responsible_cpf' => $isIndividual
                    ? BrazilianDocument::digits($data->cpf)
                    : BrazilianDocument::digits($data->responsibleCpf),
                'timezone' => 'America/Sao_Paulo',
                'currency' => 'BRL',
                'onboarding_completed_at' => now(),
            ]);

            Membership::query()->create([
                'tenant_id' => $tenant->id,
                'user_id' => $lockedUser->id,
                'role' => TeamRole::Owner,
            ]);

            $lockedUser->forceFill([
                'cpf' => $isIndividual
                    ? BrazilianDocument::digits($data->cpf)
                    : BrazilianDocument::digits($data->responsibleCpf),
                'phone' => BrazilianDocument::digits($data->phone),
                'current_team_id' => $tenant->id,
            ])->save();

            $user->forceFill(['current_team_id' => $tenant->id]);
            $user->setRelation('currentTeam', $tenant);

            return $tenant;
        });

        Session::put('tenant_id', $tenant->id);
        Session::forget('onboarding.pending');

        return $tenant;
    }
}
