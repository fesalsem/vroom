<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDownPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth middleware handles access
    }

    public function rules(): array
    {
        return [
            'down_payment_cents' => ['required', 'integer', 'min:0', 'max:999999999'],
        ];
    }
}
