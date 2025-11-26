<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Campus;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class SemiPermanentEmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnError, SkipsOnFailure
{
    protected $errors = [];
    protected $successCount = 0;
    protected $skipCount = 0;

    /**
     * Traiter chaque ligne du CSV/Excel pour les employés SEMI-PERMANENTS
     */
    public function model(array $row)
    {
        // Vérifier que l'email n'existe pas déjà
        if (User::where('email', $row['email'])->exists()) {
            $this->skipCount++;
            $this->errors[] = "Ligne ignorée: L'email {$row['email']} existe déjà";
            return null;
        }

        // Générer l'employee_id automatiquement
        $year = date('Y');
        $lastEmployee = User::where('employee_id', 'like', "EMP-{$year}-%")
            ->orderBy('employee_id', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_id, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $employeeId = sprintf('EMP-%s-%04d', $year, $newNumber);

        // Parser les jours de travail (ex: "lundi,mercredi,vendredi")
        $joursTravail = null;
        if (!empty($row['jours_travail'])) {
            $jours = array_map('trim', explode(',', strtolower($row['jours_travail'])));
            // Valider et normaliser les jours
            $joursValides = [];
            $mapping = [
                'lundi' => 'lundi',
                'mardi' => 'mardi',
                'mercredi' => 'mercredi',
                'jeudi' => 'jeudi',
                'vendredi' => 'vendredi',
                'samedi' => 'samedi',
                'dimanche' => 'dimanche',
                'lun' => 'lundi',
                'mar' => 'mardi',
                'mer' => 'mercredi',
                'jeu' => 'jeudi',
                'ven' => 'vendredi',
                'sam' => 'samedi',
                'dim' => 'dimanche',
            ];

            foreach ($jours as $jour) {
                if (isset($mapping[$jour])) {
                    $joursValides[] = $mapping[$jour];
                }
            }

            if (!empty($joursValides)) {
                $joursTravail = json_encode(array_unique($joursValides));
            }
        }

        // Créer l'utilisateur semi-permanent
        $user = User::create([
            'employee_id' => $employeeId,
            'first_name' => $row['prenom'],
            'last_name' => $row['nom'],
            'email' => $row['email'],
            'phone' => $row['telephone'] ?? null,
            'password' => Hash::make($row['mot_de_passe']),
            'employee_type' => 'semi_permanent', // Type fixe pour semi-permanents
            'monthly_salary' => $row['salaire_mensuel'] ?? null,
            'volume_horaire_hebdomadaire' => $row['volume_horaire_hebdomadaire'] ?? null,
            'jours_travail' => $joursTravail,
            'role_id' => 2, // Role Employee
            'is_active' => $this->parseBoolean($row['actif'] ?? 'oui'),
        ]);

        // Assigner les campus (sans shifts pour semi-permanents)
        if (!empty($row['campus'])) {
            $campusNames = array_map('trim', explode(',', $row['campus']));

            foreach ($campusNames as $campusName) {
                $campus = Campus::where('name', 'like', "%{$campusName}%")->first();

                if ($campus) {
                    $user->campuses()->attach($campus->id);
                }
            }
        }

        $this->successCount++;
        return $user;
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'mot_de_passe' => 'required|string|min:6',
            'salaire_mensuel' => 'nullable|numeric|min:0',
            'volume_horaire_hebdomadaire' => 'nullable|numeric|min:0',
            'jours_travail' => 'nullable|string',
            'campus' => 'nullable|string',
            'actif' => 'nullable|string',
        ];
    }

    /**
     * Parser un boolean depuis le CSV
     */
    private function parseBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim($value));
        return in_array($value, ['oui', 'yes', '1', 'true', 'vrai']);
    }

    /**
     * Gérer les erreurs de validation
     */
    public function onError(Throwable $e)
    {
        $this->errors[] = $e->getMessage();
    }

    /**
     * Gérer les échecs de validation
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = "Ligne {$failure->row()}: " . implode(', ', $failure->errors());
        }
    }

    /**
     * Obtenir les résultats de l'import
     */
    public function getResults(): array
    {
        return [
            'success' => $this->successCount,
            'skipped' => $this->skipCount,
            'errors' => $this->errors,
        ];
    }
}
