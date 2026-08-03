<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Appointment */
class AppointmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'status' => $this->status->value, 'starts_at' => $this->starts_at->toIso8601String(), 'ends_at' => $this->ends_at->toIso8601String(), 'duration_minutes' => $this->duration_minutes, 'total_cents' => $this->total_cents, 'customer' => new CustomerResource($this->whenLoaded('customer')), 'items' => $this->whenLoaded('items')];
    }
}
