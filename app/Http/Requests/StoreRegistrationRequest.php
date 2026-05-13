<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegistrationRequest extends FormRequest
{
    /**
     * Public form — anyone can register for a test drive.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for public test drive registration.
     *
     * - Name: required, free-text
     * - Email: required, valid format, max 255
     * - Phone: required, supports international formats
     * - Car model: optional (controller sets default), validated if provided
     */
    public function rules(): array
    {
        return [
            'customer_name' => [
                'required',
                'string',
                'max:255',
            ],
            'customer_email' => [
                'required',
                'email:filter',
                'max:255',
            ],
            'customer_phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^[0-9\+\-\(\)\s]+$/',
            ],
            'car_model' => [
                'sometimes',
                'string',
                'max:100',
            ],
        ];
    }

    /**
     * User-friendly validation messages.
     */
    public function messages(): array
    {
        return [
            'customer_name.required' => 'Please enter your full name.',
            'customer_name.max' => 'Name must not exceed 255 characters.',
            'customer_email.required' => 'Please enter your email address.',
            'customer_email.email' => 'Please enter a valid email address.',
            'customer_email.max' => 'Email must not exceed 255 characters.',
            'customer_phone.required' => 'Please enter your phone number.',
            'customer_phone.regex' => 'Phone number may only contain digits, spaces, +, -, and parentheses.',
            'customer_phone.max' => 'Phone number must not exceed 20 characters.',
        ];
    }
}
