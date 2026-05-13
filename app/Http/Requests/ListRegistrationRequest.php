<?php

namespace App\Http\Requests;

use App\Enums\RegistrationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth middleware handles access
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::enum(RegistrationStatus::class)],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }
}
