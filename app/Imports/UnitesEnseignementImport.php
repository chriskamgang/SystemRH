<?php

namespace App\Imports;

use App\Models\UniteEnseignement;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;

class UnitesEnseignementImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnError
{
    use SkipsErrors;

    private $rowCount = 0;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $this->rowCount++;

        // Vérifier si le code UE existe déjà
        $existing = UniteEnseignement::where('code_ue', $row['code_ue'])->first();

        if ($existing) {
            // Si existe déjà, on ignore (ou on pourrait mettre à jour)
            return null;
        }

        return new UniteEnseignement([
            'code_ue' => $row['code_ue'],
            'nom_matiere' => $row['nom_matiere'],
            'volume_horaire_total' => $row['volume_horaire_total'],
            'annee_academique' => $row['annee_academique'] ?? date('Y') . '-' . (date('Y') + 1),
            'semestre' => $row['semestre'] ?? null,
            'specialite' => $row['specialite'] ?? null,
            'niveau' => $row['niveau'] ?? null,
            'statut' => 'non_activee',
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'code_ue' => 'required|string|max:50',
            'nom_matiere' => 'required|string|max:255',
            'volume_horaire_total' => 'required|numeric|min:0.5|max:999',
            'annee_academique' => 'nullable|string|max:20',
            'semestre' => 'nullable|integer|in:1,2',
            'specialite' => 'nullable|string|max:255',
            'niveau' => 'nullable|string|max:255',
        ];
    }

    /**
     * Obtenir le nombre de lignes importées
     */
    public function getRowCount(): int
    {
        return $this->rowCount;
    }
}
