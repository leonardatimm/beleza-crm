<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Domain\Finance\Enums\FinancialEntryType;
use App\Models\FinancialEntry;

class CreateManualFinancialEntry
{
    public function handle(
        int $tenantId,
        int $actorId,
        FinancialEntryType $type,
        string $description,
        int $amountCents,
        string $dueDate,
        ?int $categoryId = null,
    ): FinancialEntry {
        return FinancialEntry::withoutGlobalScopes()->create([
            'tenant_id' => $tenantId,
            'created_by' => $actorId,
            'category_id' => $categoryId,
            'type' => $type,
            'status' => FinancialEntryStatus::Pending,
            'origin_type' => 'manual',
            'description' => trim($description),
            'amount_cents' => $amountCents,
            'due_date' => $dueDate,
            'idempotency_key' => null,
        ]);
    }
}
