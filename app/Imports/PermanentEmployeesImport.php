<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Campus;
use App\Models\UserCampusShift;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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
    protected $updateCount = 0;
    protected $skipCount = 0;
    protected $emailModifications = []; // Pour tracker les emails modifiés
    protected static $defaultPasswordHash = null;

    /**
     * Traiter chaque ligne du CSV/Excel pour les employés PERMANENTS
     */
    public function model(array $row)
    {
        // Vérifier si l'utilisateur existe déjà (par email)
        $existingUser = User::where('email', $row['email'])->first();

        // Utiliser une transaction pour garantir l'unicité de l'employee_id
        try {
            $user = DB::transaction(function () use ($row, $existingUser) {
                // Utiliser un mot de passe par défaut pour accélérer l'import
                if (self::$defaultPasswordHash === null) {
                    self::$defaultPasswordHash = Hash::make('password123');
                }

                $email = $row['email'];

                // SI l'email existe MAIS nom/prénom différents → Générer un nouvel email unique
                if ($existingUser) {
                    $sameFirstName = strtolower(trim($existingUser->first_name)) === strtolower(trim($row['prenom']));
                    $sameLastName = strtolower(trim($existingUser->last_name)) === strtolower(trim($row['nom']));

                    if (!$sameFirstName || !$sameLastName) {
                        // C'est une personne DIFFÉRENTE avec le même email
                        // Générer un email unique en ajoutant un suffixe
                        $originalEmail = $row['email'];
                        $email = $this->generateUniqueEmail($originalEmail);

                        // Logger la modification pour informer l'utilisateur
                        $this->emailModifications[] = [
                            'original' => $originalEmail,
                            'modified' => $email,
                            'name' => trim($row['prenom']) . ' ' . trim($row['nom']),
                            'reason' => 'Email déjà utilisé par ' . $existingUser->full_name
                        ];

                        $existingUser = null; // Forcer la création d'un nouvel utilisateur
                    }
                }

                $userData = [
                    'first_name' => $row['prenom'],
                    'last_name' => $row['nom'],
                    'email' => $email, // Email original ou modifié
                    'phone' => $row['telephone'] ?? null,
                    'employee_type' => 'enseignant_titulaire', // Type fixe pour permanents
                    'monthly_salary' => $row['salaire_mensuel'] ?? null,
                    'role_id' => 2, // Role Employee
                    'is_active' => $this->parseBoolean($row['actif'] ?? 'oui'),
                ];

                if ($existingUser) {
                    // MISE À JOUR de l'employé existant (même nom/prénom)
                    $existingUser->update($userData);
                    $this->updateCount++;
                    return $existingUser;
                } else {
                    // CRÉATION d'un nouvel employé
                    // Générer l'employee_id automatiquement avec verrouillage
                    $year = date('Y');

                    // Verrouiller la table pour éviter les doublons en parallèle
                    $lastEmployee = User::where('employee_id', 'like', "EMP-{$year}-%")
                        ->orderBy('employee_id', 'desc')
                        ->lockForUpdate()
                        ->first();

                    if ($lastEmployee) {
                        $lastNumber = (int) substr($lastEmployee->employee_id, -4);
                        $newNumber = $lastNumber + 1;
                    } else {
                        $newNumber = 1;
                    }

                    $employeeId = sprintf('EMP-%s-%04d', $year, $newNumber);

                    // Ajouter employee_id et password pour la création
                    $userData['employee_id'] = $employeeId;
                    $userData['password'] = self::$defaultPasswordHash;

                    return User::create($userData);
                }
            });
        } catch (\Exception $e) {
            // Si une erreur se produit, ignorer cette ligne
            $this->skipCount++;
            $this->errors[] = "Ligne ignorée pour {$row['email']}: " . $e->getMessage();
            return null;
        }

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
            'email' => 'required|email', // Supprimé 'unique' pour permettre les mises à jour
            'mot_de_passe' => 'nullable|string|min:6', // Optionnel car on utilise un mot de passe par défaut
            'salaire_mensuel' => 'nullable|numeric|min:0',
            'campus' => 'nullable|string',
            'travail_matin' => 'nullable|string',
            'travail_soir' => 'nullable|string',
            'actif' => 'nullable|string',
        ];
    }

    /**
     * Générer un email unique en ajoutant un suffixe (_1, _2, _3, etc.)
     */
    private function generateUniqueEmail(string $originalEmail): string
    {
        // Séparer la partie locale et le domaine (ex: jean.dupont@university.ga)
        $parts = explode('@', $originalEmail);
        $localPart = $parts[0]; // jean.dupont
        $domain = $parts[1] ?? 'university.ga'; // university.ga

        $suffix = 1;
        $newEmail = $originalEmail;

        // Chercher un email disponible en ajoutant _1, _2, _3, etc.
        while (User::where('email', $newEmail)->exists()) {
            $newEmail = "{$localPart}_{$suffix}@{$domain}";
            $suffix++;

            // Sécurité : éviter une boucle infinie
            if ($suffix > 100) {
                // Si on dépasse 100 tentatives, utiliser un timestamp
                $newEmail = "{$localPart}_" . time() . "@{$domain}";
                break;
            }
        }

        return $newEmail;
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
            'updated' => $this->updateCount,
            'skipped' => $this->skipCount,
            'errors' => $this->errors,
            'email_modifications' => $this->emailModifications,
        ];
    }
}
