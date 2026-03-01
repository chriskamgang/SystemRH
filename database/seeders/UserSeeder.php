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
        $deptArch = DB::table('departments')->where('code', 'DEPT-ARCH')->first();
        $deptMgmt = DB::table('departments')->where('code', 'DEPT-MGMT')->first();
        $deptInfo = DB::table('departments')->where('code', 'DEPT-INFO')->first();

        $users = [
            [
                'first_name' => 'Admin',
                'last_name' => 'Système',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin123'),
                'phone' => '+241 01 23 45 67',
                'employee_type' => 'direction',
                'department_id' => null,
                'role_id' => $adminRole->id,
                'monthly_salary' => null,
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
                'monthly_salary' => null,
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
                'department_id' => $deptArch->id,
                'role_id' => $respCampusRole->id,
                'monthly_salary' => null,
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
                'monthly_salary' => null,
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
                'department_id' => $deptMgmt->id,
                'role_id' => $employeRole->id,
                'monthly_salary' => null,
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
                'monthly_salary' => null,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Thomas',
                'last_name' => 'KAMGA',
                'email' => 'thomas.kamga@insam.cm',
                'password' => Hash::make('password123'),
                'phone' => '+237 06 70 80 90',
                'employee_type' => 'enseignant_titulaire',
                'department_id' => $deptArch->id,
                'role_id' => $employeRole->id,
                'monthly_salary' => 350000.00,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'first_name' => 'Clarisse',
                'last_name' => 'TCHOUMI',
                'email' => 'clarisse.tchoumi@insam.cm',
                'password' => Hash::make('password123'),
                'phone' => '+237 06 71 82 93',
                'employee_type' => 'semi_permanent',
                'department_id' => $deptMgmt->id,
                'role_id' => $employeRole->id,
                'monthly_salary' => 350000.00,
                'is_active' => true,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('users')->insert($users);

        // Assigner les campus aux utilisateurs
        $adminUser = DB::table('users')->where('email', 'admin@gmail.com')->first();
        $jeanUser = DB::table('users')->where('email', 'jean.mbongo@university.ga')->first();
        $marieUser = DB::table('users')->where('email', 'marie.okome@university.ga')->first();
        $paulUser = DB::table('users')->where('email', 'paul.ndong@university.ga')->first();
        $thomasUser = DB::table('users')->where('email', 'thomas.kamga@insam.cm')->first();
        $clarisseUser = DB::table('users')->where('email', 'clarisse.tchoumi@insam.cm')->first();

        $campusInsam = DB::table('campuses')->where('code', 'INSAM-BFM')->first();

        $userCampus = [
            // Admin a accès au campus INSAM
            ['user_id' => $adminUser->id, 'campus_id' => $campusInsam->id, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],

            // Jean (Chef dept) - INSAM Bafoussam
            ['user_id' => $jeanUser->id, 'campus_id' => $campusInsam->id, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],

            // Marie (Resp campus) - INSAM Bafoussam
            ['user_id' => $marieUser->id, 'campus_id' => $campusInsam->id, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],

            // Paul (Vacataire) - INSAM Bafoussam
            ['user_id' => $paulUser->id, 'campus_id' => $campusInsam->id, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],

            // Thomas (Permanent) - INSAM Bafoussam
            ['user_id' => $thomasUser->id, 'campus_id' => $campusInsam->id, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],

            // Clarisse (Semi-permanent) - INSAM Bafoussam
            ['user_id' => $clarisseUser->id, 'campus_id' => $campusInsam->id, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('user_campus')->insert($userCampus);

        $this->command->info('✅ ' . count($users) . ' utilisateurs créés avec succès');
        $this->command->info('   📧 Email: admin@gmail.com');
        $this->command->info('   🔑 Password: admin123');
        $this->command->info('');
        $this->command->info('   Autres utilisateurs de test :');
        $this->command->info('   - jean.mbongo@university.ga (Chef département)');
        $this->command->info('   - marie.okome@university.ga (Responsable campus)');
        $this->command->info('   - paul.ndong@university.ga (Enseignant vacataire)');
        $this->command->info('   - sophie.essono@university.ga (Administratif)');
        $this->command->info('   - andre.obiang@university.ga (Technique)');
        $this->command->info('   - thomas.kamga@insam.cm (Enseignant permanent - 350,000 FCFA/mois)');
        $this->command->info('   - clarisse.tchoumi@insam.cm (Enseignant semi-permanent - 350,000 FCFA/mois)');
        $this->command->info('   🔑 Tous avec le mot de passe: password123');
    }
}
