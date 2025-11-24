<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Démarrage du seeding de la base de données...');
        $this->command->info('');

        // Ordre important : respecter les dépendances entre tables
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            CampusSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('✅ Base de données remplie avec succès !');
        $this->command->info('');
        $this->command->info('🎉 Vous pouvez maintenant vous connecter avec :');
        $this->command->info('   📧 Email: admin@university.ga');
        $this->command->info('   🔑 Password: password123');
    }
}
