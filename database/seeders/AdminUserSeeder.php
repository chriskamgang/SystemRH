<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un utilisateur administrateur de test
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'System',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin123'),
                'employee_type' => 'direction',
                'role_id' => 1, // Admin
                'is_active' => true,
                'phone' => '+241 00 00 00 00',
            ]
        );

        $this->command->info('✅ Utilisateur admin créé avec succès!');
        $this->command->info('📧 Email: admin@gmail.com');
        $this->command->info('🔑 Mot de passe: admin123');
    }
}
