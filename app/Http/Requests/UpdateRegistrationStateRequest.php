<?php

namespace App\Http\Requests;

use App\Enums\RegistrationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegistrationStateRequest extends FormRequest
{
    /**
     * Agent-only — auth middleware handles access control.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for state transitions.
     *
     * The status must be a valid RegistrationStatus enum value.
     * Business logic validation (allowed transitions) is handled
     * by RegistrationStateService, not in this form request.
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::enum(RegistrationStatus::class),
            ],
        ];
    }

    /**
     * User-friendly validation messages.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Please select a status.',
            'status.Illuminate\Validation\Rules\Enum' => 'The selected status is not valid.',
        ];
    }
}
