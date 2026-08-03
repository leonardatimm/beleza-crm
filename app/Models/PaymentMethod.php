<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tenant_id', 'name', 'is_active'])]
class PaymentMethod extends Model
{
    use BelongsToTenant;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
