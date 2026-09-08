<?php

namespace App\Http\Requests\Bus;

use Illuminate\Foundation\Http\FormRequest;

class PositionRequest extends FormRequest
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
            'vitesse_kmh' => ['nullable', 'integer', 'min:0', 'max:200'],
            'cap_degres' => ['nullable', 'integer', 'min:0', 'max:359'],
        ];
    }
}
