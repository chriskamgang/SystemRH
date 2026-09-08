<?php

namespace App\Http\Requests\Bus;

use Illuminate\Foundation\Http\FormRequest;

/** Connexion du chauffeur : son numero et son code a 4 chiffres. */
class ConnexionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'telephone' => ['required', 'string', 'max:20'],
            'pin' => ['required', 'string', 'regex:/^\d{4}$/'],
            'appareil' => ['nullable', 'string', 'max:100'],
        ];
    }
}
