<?php

namespace App\Http\Requests\Bus;

use App\Enums\TypeLieu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Changement du point de ramassage habituel (3.1).
 *
 * Distinct de la completion de profil : l'etudiant qui demenage ne doit pas
 * avoir a ressaisir son identite pour corriger son arret.
 */
class ChangerPointRamassageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'lieu_ramassage_id' => [
                'required', 'integer',
                Rule::exists('lieux', 'id')->where(
                    fn ($q) => $q
                        ->where('actif', true)
                        ->where('type', TypeLieu::Ramassage->value),
                ),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'lieu_ramassage_id.required' => 'Choisis ton nouveau point de ramassage.',
            'lieu_ramassage_id.exists' => 'Ce point de ramassage n’est pas desservi.',
        ];
    }
}
