<?php

namespace App\Http\Requests\Bus;

use App\Enums\SensParcours;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Coordonnees GPS accompagnant tout appui sur un bouton d'etape (regle 3.4). */
class PointageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],

            // Sens du tour, au demarrage : aller le matin, retour le soir.
            // Absent, il est deduit de l'heure.
            'sens' => ['nullable', Rule::enum(SensParcours::class)],
        ];
    }
}
