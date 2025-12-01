<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Campus;
use App\Models\UserCampusShift;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

class PermanentEmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnError, SkipsOnFailure
{
    protected $errors = [];
    protected $successCount = 0;
    protected $skipCount = 0;

    /**
     * Traiter chaque ligne du CSV/Excel pour les employés PERMANENTS
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

        // Créer l'utilisateur permanent
        $user = User::create([
            'employee_id' => $employeeId,
            'first_name' => $row['prenom'],
            'last_name' => $row['nom'],
            'email' => $row['email'],
            'phone' => $row['telephone'] ?? null,
            'password' => Hash::make($row['mot_de_passe']),
            'employee_type' => 'enseignant_titulaire', // Type fixe pour permanents
            'monthly_salary' => $row['salaire_mensuel'] ?? null,
            'role_id' => 2, // Role Employee
            'is_active' => $this->parseBoolean($row['actif'] ?? 'oui'),
        ]);

        // Assigner les campus avec shifts (matin/soir)
        if (!empty($row['campus'])) {
            $campusNames = array_map('trim', explode(',', $row['campus']));
            $campusNames = array_unique($campusNames); // Éviter les doublons dans le CSV

            foreach ($campusNames as $campusName) {
                $campus = Campus::where('name', 'like', "%{$campusName}%")->first();

                if ($campus) {
                    // Vérifier si la relation n'existe pas déjà avant d'attacher
                    if (!$user->campuses()->where('campus_id', $campus->id)->exists()) {
                        $user->campuses()->attach($campus->id);
                    }

                    // Créer les shifts pour les permanents
                    $travailMatin = $this->parseBoolean($row['travail_matin'] ?? 'non');
                    $travailSoir = $this->parseBoolean($row['travail_soir'] ?? 'non');

                    if ($travailMatin) {
                        // Utiliser updateOrCreate pour éviter les doublons
                        UserCampusShift::updateOrCreate(
                            [
                                'user_id' => $user->id,
                                'campus_id' => $campus->id,
                                'shift_type' => 'morning',
                            ],
                            [
                                'start_time' => '08:00:00',
                                'end_time' => '13:00:00',
                            ]
                        );
                    }

                    if ($travailSoir) {
                        // Utiliser updateOrCreate pour éviter les doublons
                        UserCampusShift::updateOrCreate(
                            [
                                'user_id' => $user->id,
                                'campus_id' => $campus->id,
                                'shift_type' => 'evening',
                            ],
                            [
                                'start_time' => '14:00:00',
                                'end_time' => '19:00:00',
                            ]
                        );
                    }
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
            'campus' => 'nullable|string',
            'travail_matin' => 'nullable|string',
            'travail_soir' => 'nullable|string',
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
