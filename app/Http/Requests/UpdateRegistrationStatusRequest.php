<?php

namespace App\Http\Requests;

use App\Enums\RegistrationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegistrationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth middleware handles access
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(RegistrationStatus::class)],
        ];
    }
}
