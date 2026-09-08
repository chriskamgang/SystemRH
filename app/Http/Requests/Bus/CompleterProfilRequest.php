<?php

namespace App\Http\Requests\Bus;

use App\Enums\TypeLieu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Completion du profil etudiant apres la premiere connexion (3.1).
 *
 * Reprend les trois etapes de l'application : identite, matricule, puis
 * point de ramassage. Le campus n'est pas demande : il change d'un jour a
 * l'autre selon l'emploi du temps.
 */
class CompleterProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'prenom' => ['required', 'string', 'max:255'],
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => [
                'required', 'string', 'max:20',
                // La table fusionnee range le telephone du transport dans
                // `telephone_bus` : `phone` appartient a Estuaire RH.
                Rule::unique('users', 'telephone_bus')->ignore($userId),
            ],

            // Vide quand l'etudiant a coche « j'ai oublie mon matricule » :
            // il le renseignera plus tard depuis son profil.
            'matricule_insam' => [
                'nullable', 'string', 'max:50',
                Rule::unique('etudiants', 'matricule_insam')
                    ->ignore($userId, 'user_id'),
            ],

            // Scolarite : ces deux champs rattachent l'etudiant a ses UE,
            // donc a son emploi du temps cote Estuaire RH. Facultatifs —
            // l'etudiant qui ne les connait pas encore garde un compte
            // transport pleinement fonctionnel, sans emploi du temps.
            'niveau' => ['nullable', 'string', 'max:100'],
            'specialite' => ['nullable', 'string', 'max:150'],

            // Le point choisi doit etre un lieu de ramassage actif, et non
            // un campus ou un arret hors service.
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
            'telephone.unique' => 'Ce numéro est déjà associé à un autre compte.',
            'matricule_insam.unique' => 'Ce matricule est déjà utilisé.',
            'lieu_ramassage_id.required' => 'Choisis ton point de ramassage.',
            'lieu_ramassage_id.exists' => 'Ce point de ramassage n’est pas desservi.',
        ];
    }
}
