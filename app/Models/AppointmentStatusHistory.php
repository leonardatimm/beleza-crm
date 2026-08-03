<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tenant_id', 'appointment_id', 'changed_by', 'from_status', 'to_status', 'reason'])]
class AppointmentStatusHistory extends Model
{
    use BelongsToTenant;
}
