<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Récupérer les rôles
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        $chefDeptRole = DB::table('roles')->where('name', 'chef_departement')->first();
        $respCampusRole = DB::table('roles')->where('name', 'responsable_campus')->first();
        $employeRole = DB::table('roles')->where('name', 'employe')->first();

        // Récupérer quelques départements
        $deptInfo = DB::table('departments')->where('code', 'DEPT-INFO')->first();
        $deptMath = DB::table('departments')->where('code', 'DEPT-MATH')->first();
        $deptLitt = DB::table('departments')->where('code', 'DEPT-LITT')->first();

        $users = [
            [
                'first_name' => 'Admin',
                'last_name' => 'Système',
                'email' => 'admin@university.ga',
                'password' => Hash::make('password123'),
                'phone' => '+241 01 23 45 67',
                'employee_type' => 'direction',
                'department_id' => null,
                'role_id' => $adminRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Jean',
                'last_name' => 'MBONGO',
                'email' => 'jean.mbongo@university.ga',
                'password' => Hash::make('password123'),
                'phone' => '+241 02 34 56 78',
                'employee_type' => 'enseignant_titulaire',
                'department_id' => $deptInfo->id,
                'role_id' => $chefDeptRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Marie',
                'last_name' => 'OKOME',
                'email' => 'marie.okome@university.ga',
                'password' => Hash::make('password123'),
                'phone' => '+241 03 45 67 89',
                'employee_type' => 'enseignant_titulaire',
                'department_id' => $deptMath->id,
                'role_id' => $respCampusRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Paul',
                'last_name' => 'NDONG',
                'email' => 'paul.ndong@university.ga',
                'password' => Hash::make('password123'),
                'phone' => '+241 04 56 78 90',
                'employee_type' => 'enseignant_vacataire',
                'department_id' => $deptInfo->id,
                'role_id' => $employeRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Sophie',
                'last_name' => 'ESSONO',
                'email' => 'sophie.essono@university.ga',
                'password' => Hash::make('password123'),
                'phone' => '+241 05 67 89 01',
                'employee_type' => 'administratif',
                'department_id' => $deptLitt->id,
                'role_id' => $employeRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'André',
                'last_name' => 'OBIANG',
                'email' => 'andre.obiang@university.ga',
                'password' => Hash::make('password123'),
                'phone' => '+241 06 78 90 12',
                'employee_type' => 'technique',
                'department_id' => null,
                'role_id' => $employeRole->id,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);

        // Assigner les campus aux utilisateurs
        $adminUser = DB::table('users')->where('email', 'admin@university.ga')->first();
        $jeanUser = DB::table('users')->where('email', 'jean.mbongo@university.ga')->first();
        $marieUser = DB::table('users')->where('email', 'marie.okome@university.ga')->first();
        $paulUser = DB::table('users')->where('email', 'paul.ndong@university.ga')->first();

        $campusTech = DB::table('campuses')->where('code', 'CAM-TECH')->first();
        $campusSci = DB::table('campuses')->where('code', 'CAM-SCI')->first();
        $campusLet = DB::table('campuses')->where('code', 'CAM-LET')->first();

        $userCampus = [
            // Admin a accès à tous les campus
            ['user_id' => $adminUser->id, 'campus_id' => $campusTech->id, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $adminUser->id, 'campus_id' => $campusSci->id, 'is_primary' => false, 'created_at' => now(), 'updated_at' => now()],

            // Jean (Chef dept) - Campus Tech
            ['user_id' => $jeanUser->id, 'campus_id' => $campusTech->id, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],

            // Marie (Resp campus) - Campus Sciences
            ['user_id' => $marieUser->id, 'campus_id' => $campusSci->id, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],

            // Paul (Vacataire) - Plusieurs campus
            ['user_id' => $paulUser->id, 'campus_id' => $campusTech->id, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $paulUser->id, 'campus_id' => $campusLet->id, 'is_primary' => false, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('user_campus')->insert($userCampus);

        $this->command->info('✅ ' . count($users) . ' utilisateurs créés avec succès');
        $this->command->info('   📧 Email: admin@university.ga');
        $this->command->info('   🔑 Password: password123');
        $this->command->info('');
        $this->command->info('   Autres utilisateurs de test :');
        $this->command->info('   - jean.mbongo@university.ga (Chef département)');
        $this->command->info('   - marie.okome@university.ga (Responsable campus)');
        $this->command->info('   - paul.ndong@university.ga (Enseignant vacataire)');
        $this->command->info('   - sophie.essono@university.ga (Administratif)');
        $this->command->info('   - andre.obiang@university.ga (Technique)');
        $this->command->info('   🔑 Tous avec le mot de passe: password123');
    }
}
