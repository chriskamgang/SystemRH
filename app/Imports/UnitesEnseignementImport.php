<?php

namespace App\Imports;

use App\Models\UniteEnseignement;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\Importable;

class UnitesEnseignementImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithBatchInserts
{
    use SkipsFailures, Importable;

    private $rowCount = 0;
    private $skipped = 0;

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Vérifier si le code UE existe déjà
        if (isset($row['code_ue']) && UniteEnseignement::where('code_ue', $row['code_ue'])->exists()) {
            $this->skipped++;
            return null;
        }

        $this->rowCount++;

        // Extraire le numéro de semestre si c'est un texte "Semestre X"
        $semestre = null;
        if (!empty($row['semestre'])) {
            if (is_numeric($row['semestre'])) {
                $semestre = (int) $row['semestre'];
            } elseif (preg_match('/\d+/', $row['semestre'], $matches)) {
                // Extrait le premier nombre trouvé dans "Semestre 7", "S8", etc.
                $semestre = (int) $matches[0];
            }
        }

        return new UniteEnseignement([
            'code_ue' => $row['code_ue'] ?? null,
            'nom_matiere' => $row['nom_matiere'] ?? null,
            'volume_horaire_total' => $row['volume_horaire_total'] ?? null,
            'annee_academique' => $row['annee_academique'] ?? date('Y') . '-' . (date('Y') + 1),
            'semestre' => $semestre,
            'specialite' => $row['specialite'] ?? null,
            'niveau' => $row['niveau'] ?? null,
            'statut' => 'non_activee',
            'created_by' => Auth::id(),
            'enseignant_id' => null, // Explicitly set to null for library units
        ]);
    }

    /**
     * Taille du batch pour l'insertion
     */
    public function batchSize(): int
    {
        return 100;
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
            'semestre' => 'nullable', // Accepter n'importe quelle valeur, on va l'extraire
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

    /**
     * Obtenir le nombre de lignes ignorées
     */
    public function getSkippedCount(): int
    {
        return $this->skipped;
    }

    /**
     * Obtenir les erreurs sous forme de messages
     */
    public function getErrors(): array
    {
        $errorMessages = [];

        foreach ($this->failures() as $failure) {
            $errorMessages[] = "Ligne {$failure->row()}: " . implode(', ', $failure->errors());
        }

        return $errorMessages;
    }

    /**
     * Messages de validation personnalisés
     */
    public function customValidationMessages()
    {
        return [
            'code_ue.required' => 'Le code UE est obligatoire à la ligne :row',
            'nom_matiere.required' => 'Le nom de la matière est obligatoire à la ligne :row',
            'volume_horaire_total.required' => 'Le volume horaire est obligatoire à la ligne :row',
            'volume_horaire_total.numeric' => 'Le volume horaire doit être un nombre à la ligne :row',
            'volume_horaire_total.min' => 'Le volume horaire minimum est 0.5 à la ligne :row',
            'volume_horaire_total.max' => 'Le volume horaire maximum est 999 à la ligne :row',
            'semestre.integer' => 'Le semestre doit être un nombre à la ligne :row',
            'semestre.in' => 'Le semestre doit être 1 ou 2 à la ligne :row',
        ];
    }
}
