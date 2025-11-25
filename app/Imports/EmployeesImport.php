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

class EmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnError, SkipsOnFailure
{
    protected $errors = [];
    protected $successCount = 0;
    protected $skipCount = 0;

    /**
     * Traiter chaque ligne du CSV/Excel
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

        // Créer l'employé
        $user = User::create([
            'employee_id' => $employeeId,
            'first_name' => $row['prenom'],
            'last_name' => $row['nom'],
            'email' => $row['email'],
            'phone' => $row['telephone'] ?? null,
            'password' => Hash::make($row['mot_de_passe']),
            'employee_type' => $this->mapEmployeeType($row['type_employe']),
            'monthly_salary' => $row['salaire_mensuel'] ?? null,
            'hourly_rate' => $row['taux_horaire'] ?? null,
            'role_id' => 2, // Role "Employé" par défaut
            'is_active' => strtolower($row['actif']) === 'oui' ? 1 : 0,
        ]);

        // Assigner les campus avec plages horaires (si fournis)
        if (!empty($row['campus'])) {
            $campusNames = explode(',', $row['campus']);
            $worksMorning = strtolower(trim($row['travail_matin'] ?? 'non')) === 'oui';
            $worksEvening = strtolower(trim($row['travail_soir'] ?? 'non')) === 'oui';

            // Pour les permanents enseignants uniquement
            $isPermanentTeacher = $user->employee_type === 'enseignant_titulaire';

            foreach ($campusNames as $campusName) {
                $campusName = trim($campusName);
                $campus = Campus::where('name', 'like', "%{$campusName}%")->first();

                if ($campus) {
                    // Attacher le campus à l'utilisateur
                    $user->campuses()->attach($campus->id);

                    // Si c'est un permanent enseignant, créer les plages horaires
                    if ($isPermanentTeacher && ($worksMorning || $worksEvening)) {
                        UserCampusShift::create([
                            'user_id' => $user->id,
                            'campus_id' => $campus->id,
                            'works_morning' => $worksMorning,
                            'works_evening' => $worksEvening,
                        ]);
                    }
                }
            }
        }

        $this->successCount++;
        return $user;
    }

    /**
     * Mapper les types d'employés du CSV vers la base de données
     */
    private function mapEmployeeType($type)
    {
        $type = strtolower(trim($type));

        $mapping = [
            'permanent' => 'enseignant_titulaire',
            'enseignant permanent' => 'enseignant_titulaire',
            'titulaire' => 'enseignant_titulaire',
            'semi-permanent' => 'semi_permanent',
            'semi permanent' => 'semi_permanent',
            'vacataire' => 'enseignant_vacataire',
            'administratif' => 'administratif',
            'technique' => 'technique',
            'direction' => 'direction',
        ];

        return $mapping[$type] ?? 'enseignant_titulaire';
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
            'type_employe' => 'required|string',
        ];
    }

    /**
     * Messages de validation personnalisés
     */
    public function customValidationMessages()
    {
        return [
            'prenom.required' => 'Le prénom est obligatoire',
            'nom.required' => 'Le nom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.email' => 'L\'email doit être valide',
            'email.unique' => 'Cet email existe déjà',
            'mot_de_passe.required' => 'Le mot de passe est obligatoire',
            'mot_de_passe.min' => 'Le mot de passe doit contenir au moins 6 caractères',
            'type_employe.required' => 'Le type d\'employé est obligatoire',
        ];
    }

    /**
     * Gérer les erreurs
     */
    public function onError(Throwable $e)
    {
        $this->errors[] = "Erreur: " . $e->getMessage();
    }

    /**
     * Gérer les échecs de validation
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->errors[] = "Ligne {$failure->row()}: {$failure->errors()[0]}";
        }
    }

    /**
     * Obtenir les erreurs
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Obtenir le nombre de succès
     */
    public function getSuccessCount()
    {
        return $this->successCount;
    }

    /**
     * Obtenir le nombre d'éléments ignorés
     */
    public function getSkipCount()
    {
        return $this->skipCount;
    }
}
