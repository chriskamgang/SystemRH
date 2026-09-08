<?php

namespace App\Http\Requests\Bus;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Inscription de l'etudiant : son adresse et le PIN qu'il choisit.
 *
 * La confirmation est demandee ici plutot que dans l'application seule :
 * quatre chiffres se saisissent vite, et une faute de frappe enfermerait
 * l'etudiant dehors des sa premiere connexion.
 */
class InscriptionPinRequest extends FormRequest
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
            'pin' => ['required', 'string', 'regex:/^\d{4}$/', 'confirmed'],
            'appareil' => ['nullable', 'string', 'max:100'],
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
