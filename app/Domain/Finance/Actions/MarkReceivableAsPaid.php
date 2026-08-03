<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\FinancialEntryStatus;
use App\Models\FinancialEntry;
use App\Models\PaymentMethod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkReceivableAsPaid
{
    public function handle(FinancialEntry $entry, int $paymentMethodId, ?string $paidAt = null): FinancialEntry
    {
        return DB::transaction(function () use ($entry, $paymentMethodId, $paidAt): FinancialEntry {
            $locked = FinancialEntry::withoutGlobalScopes()->lockForUpdate()->findOrFail($entry->id);

            if ($locked->status === FinancialEntryStatus::Paid) {
                return $locked;
            }

            if ($locked->status !== FinancialEntryStatus::Pending) {
                throw ValidationException::withMessages(['status' => 'Somente lançamentos pendentes podem ser baixados.']);
            }

            $methodExists = PaymentMethod::withoutGlobalScopes()
                ->where('tenant_id', $locked->tenant_id)
                ->where('is_active', true)
                ->whereKey($paymentMethodId)
                ->exists();

            if (! $methodExists) {
                throw ValidationException::withMessages(['paymentMethodId' => 'Forma de pagamento inválida.']);
            }

            $locked->update([
                'status' => FinancialEntryStatus::Paid,
                'payment_method_id' => $paymentMethodId,
                'paid_at' => $paidAt ? CarbonImmutable::parse($paidAt) : now(),
            ]);

            return $locked->refresh();
        }, 3);
    }
}
