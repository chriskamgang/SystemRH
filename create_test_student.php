<?php

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// S'assurer que le rôle étudiant existe ou utiliser un rôle de base
$role = Role::where('name', 'student')->first();
if (!$role) {
    $role = Role::create(['name' => 'student', 'display_name' => 'Étudiant']);
}

$email = 'etudiant@insam.com';
$user = User::where('email', $email)->first();

if ($user) {
    $user->delete();
}

$student = User::create([
    'first_name' => 'Jean',
    'last_name' => 'Etudiant',
    'email' => $email,
    'password' => Hash::make('password'),
    'employee_type' => 'etudiant',
    'specialite' => 'Informatique',
    'niveau' => 'Licence 3',
    'role_id' => $role->id,
    'is_active' => true,
    'employee_id' => 'STUD001',
]);

echo "✅ Étudiant de test créé avec succès !\n";
echo "Email: etudiant@insam.com\n";
echo "Password: password\n";
