<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    protected $successCount = 0;
    protected $updateCount = 0;
    protected $errors = [];

    public function model(array $row)
    {
        $existingUser = User::where('email', $row['email'])
            ->orWhere('employee_id', $row['matricule'])
            ->first();

        $role = Role::where('name', 'employee')->first();
        $roleId = $role ? $role->id : 2;

        $data = [
            'first_name' => $row['prenom'],
            'last_name' => $row['nom'],
            'email' => $row['email'],
            'employee_id' => $row['matricule'],
            'phone' => $row['telephone'] ?? null,
            'specialite' => $row['specialite'] ?? null,
            'niveau' => $row['niveau'] ?? null,
            'employee_type' => 'etudiant',
            'role_id' => $roleId,
            'is_active' => true,
        ];

        if ($existingUser) {
            $existingUser->update($data);
            $this->updateCount++;
            return $existingUser;
        }

        $data['password'] = Hash::make('password123');
        $this->successCount++;
        
        return new User($data);
    }

    public function rules(): array
    {
        return [
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'email' => 'required|email',
            'matricule' => 'required|string',
        ];
    }

    public function getResults(): array
    {
        return [
            'success' => $this->successCount,
            'updated' => $this->updateCount,
            'errors' => $this->errors,
        ];
    }
}
