<?php

namespace App\Http\Requests\Bus;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Battement de presence du chauffeur (CDC 3.1).
 *
 * La position est facultative : le chauffeur passe en ligne des l'ouverture
 * de l'application, avant meme que le premier point GPS ne soit fixe. Elle
 * est en revanche exigee par paire — une latitude sans longitude ne
 * designe rien.
 */
class PresenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'vitesse_kmh' => ['nullable', 'integer', 'min:0', 'max:200'],
            'cap_degres' => ['nullable', 'integer', 'min:0', 'max:359'],
        ];
    }
}
