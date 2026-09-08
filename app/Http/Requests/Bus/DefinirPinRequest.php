<?php

namespace App\Http\Requests\Bus;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Choix du PIN, a l'inscription comme apres un oubli.
 *
 * La confirmation est demandee ici plutot que dans l'application seule :
 * quatre chiffres se saisissent vite, et une faute de frappe enfermerait
 * l'etudiant dehors jusqu'au prochain code par email.
 */
class DefinirPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'pin' => ['required', 'string', 'regex:/^\d{4}$/', 'confirmed'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'pin.required' => 'Choisis un code à 4 chiffres.',
            'pin.regex' => 'Le code compte 4 chiffres.',
            'pin.confirmed' => 'Les deux codes ne correspondent pas.',
        ];
    }
}
