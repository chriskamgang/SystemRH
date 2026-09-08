<?php

namespace App\Http\Requests\Bus;

use Illuminate\Foundation\Http\FormRequest;

class VerifierOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            // Six chiffres exactement, zeros de tete compris.
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
            'appareil' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'code.required' => 'Entre les 6 chiffres du code.',
            'code.regex' => 'Le code compte 6 chiffres.',
        ];
    }
}
