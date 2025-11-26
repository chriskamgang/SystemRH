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

class VacataireEmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnError, SkipsOnFailure
{
    protected $errors = [];
    protected $successCount = 0;
    protected $skipCount = 0;

    /**
     * Traiter chaque ligne du CSV/Excel pour les employés VACATAIRES
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

        // Créer l'utilisateur vacataire
        $user = User::create([
            'employee_id' => $employeeId,
            'first_name' => $row['prenom'],
            'last_name' => $row['nom'],
            'email' => $row['email'],
            'phone' => $row['telephone'] ?? null,
            'password' => Hash::make($row['mot_de_passe']),
            'employee_type' => 'enseignant_vacataire', // Type fixe pour vacataires
            'hourly_rate' => $row['taux_horaire'] ?? null,
            'role_id' => 2, // Role Employee
            'is_active' => $this->parseBoolean($row['actif'] ?? 'oui'),
        ]);

        // Assigner les campus (sans shifts pour vacataires)
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
            'taux_horaire' => 'nullable|numeric|min:0',
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
