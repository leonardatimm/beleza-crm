<?php

namespace App\Http\Requests\Appointments;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Appointment::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['customer_id' => ['required', 'integer'], 'professional_profile_id' => ['required', 'integer'], 'starts_at' => ['required', 'date'], 'service_ids' => ['required', 'array', 'min:1'], 'service_ids.*' => ['integer'], 'notes' => ['nullable', 'string', 'max:2000']];
    }
}
