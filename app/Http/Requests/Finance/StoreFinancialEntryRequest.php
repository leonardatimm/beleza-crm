<?php

namespace App\Http\Requests\Finance;

use App\Models\FinancialEntry;
use Illuminate\Foundation\Http\FormRequest;

class StoreFinancialEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FinancialEntry::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['type' => ['required', 'in:income,expense'], 'description' => ['required', 'string', 'max:255'], 'amount_cents' => ['required', 'integer', 'min:1'], 'due_date' => ['required', 'date'], 'category_id' => ['nullable', 'integer']];
    }
}
