<?php

namespace App\Http\Requests\Services;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Service::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:1000'], 'duration_minutes' => ['required', 'integer', 'min:5', 'max:720'], 'price_cents' => ['required', 'integer', 'min:0']];
    }
}
