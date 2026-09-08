<?php

namespace App\Http\Requests\Bus;

use Illuminate\Foundation\Http\FormRequest;

/** Connexion courante de l'etudiant : son adresse et son PIN. */
class ConnexionPinRequest extends FormRequest
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
            // Quatre chiffres exactement, zeros de tete compris.
            'pin' => ['required', 'string', 'regex:/^\d{4}$/'],
            'appareil' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'pin.required' => 'Entre ton code à 4 chiffres.',
            'pin.regex' => 'Le code compte 4 chiffres.',
        ];
    }
}
