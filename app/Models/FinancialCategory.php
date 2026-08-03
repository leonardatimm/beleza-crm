<?php

namespace App\Models;

use App\Domain\Finance\Enums\FinancialEntryType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tenant_id', 'name', 'type', 'is_active'])]
class FinancialCategory extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return ['type' => FinancialEntryType::class, 'is_active' => 'boolean'];
    }
}
