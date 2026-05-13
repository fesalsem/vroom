<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRegistrationRequest extends FormRequest
{
    /**
     * Agent-only — auth middleware handles access control.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for updating registration details.
     *
     * Currently supports:
     * - down_payment_cents: integer cents, non-negative, max ~RM 10M
     * - notes: optional free-text for agent remarks
     *
     * All fields are optional — only send what you want to update.
     */
    public function rules(): array
    {
        return [
            'down_payment_cents' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
                'max:999999999',
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * User-friendly validation messages.
     */
    public function messages(): array
    {
        return [
            'down_payment_cents.integer' => 'Down payment must be a whole number (in cents).',
            'down_payment_cents.min' => 'Down payment cannot be negative.',
            'down_payment_cents.max' => 'Down payment amount is too large.',
            'notes.max' => 'Notes must not exceed 1000 characters.',
        ];
    }
}
