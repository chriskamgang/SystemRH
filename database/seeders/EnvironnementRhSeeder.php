<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Remet d'aplomb les donnees de reference d'Estuaire RH.
 *
 * La fusion avec INSAM BUS a laisse la base a moitie garnie : les
 * migrations du transport sont passees, mais les roles RH ne l'etaient
 * pas. Il n'en restait qu'un — `employe` — et l'administrateur pointait
 * dessus, si bien que `isAdmin()` repondait faux et que le back-office
 * refusait la connexion sans expliquer pourquoi.
 *
 * Ce seeder est rejouable : chaque ligne est posee par `updateOrInsert`,
 * la ou `RoleSeeder` et `RolePermissionSeeder` font un `insert()` sec qui
 * duplique au second passage.
 */
class EnvironnementRhSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Les quatre roles d'Estuaire RH, du plus large au plus etroit. */
    private const ROLES = [
        ['name' => 'admin', 'display_name' => 'Administrateur Central', 'description' => 'Acces complet a tous les campus et donnees.'],
        ['name' => 'chef_departement', 'display_name' => 'Chef de Departement', 'description' => 'Acces limite a son departement.'],
        ['name' => 'responsable_campus', 'display_name' => 'Responsable de Campus', 'description' => 'Acces limite a son campus.'],
        ['name' => 'employe', 'display_name' => 'Employe Standard', 'description' => 'Acces a ses propres pointages.'],

        // Regne sur INSAM BUS et sur lui seul : la flotte, le reseau,
        // l'exploitation et les abonnements. Les dossiers du personnel
        // d'Estuaire RH lui restent fermes.
        ['name' => 'admin_bus', 'display_name' => 'Administrateur Transport', 'description' => 'Acces complet a INSAM BUS, sans acces a Estuaire RH.'],
    ];

    /**
     * Permissions par role. L'administrateur les recoit toutes, il n'a
     * donc pas a figurer ici.
     */
    private const PERMISSIONS_PAR_ROLE = [
        'chef_departement' => [
            'view_own_history', 'view_department', 'view_campus',
            'view_realtime_presence', 'manage_employees',
            'generate_reports', 'export_data', 'view_statistics',
        ],
        'responsable_campus' => [
            'view_own_history', 'view_campus', 'view_realtime_presence',
            'generate_reports', 'export_data', 'view_statistics',
        ],
        'employe' => ['view_own_history'],

        // Le back-office du transport ne consulte aucune permission : la
        // barriere est posee par le middleware `espace`, qui raisonne sur
        // le role. La permission de tableau de bord lui est neanmoins
        // donnee, faute de quoi la connexion le refuserait a la porte.
        'admin_bus' => ['access_dashboard', 'view_realtime_presence'],
    ];

    public function run(): void
    {
        $roles = $this->poserLesRoles();
        $this->rattacherLesPermissions($roles);
        $this->promouvoirLAdministrateur($roles['admin']);
        $this->poserLAdministrateurTransport($roles['admin_bus']);
    }

    /**
     * Cree les roles manquants sans toucher a ceux deja en place.
     *
     * L'unicite porte sur le couple (name, company_id) depuis le passage
     * au multi-entreprises : les roles de reference restent a
     * `company_id = null`, partages par toutes les entreprises.
     *
     * @return array<string, int> le nom du role vers son identifiant
     */
    private function poserLesRoles(): array
    {
        $identifiants = [];

        foreach (self::ROLES as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name'], 'company_id' => null],
                $role + ['updated_at' => now(), 'created_at' => now()],
            );

            $identifiants[$role['name']] = DB::table('roles')
                ->where('name', $role['name'])
                ->whereNull('company_id')
                ->value('id');
        }

        $this->command->info('✅ '.count($identifiants).' rôles en place');

        return $identifiants;
    }

    /**
     * Attribue les permissions a chaque role.
     *
     * `role_permissions` n'a pas de contrainte d'unicite : un simple
     * `insert` rejoue empilerait les doublons. On efface donc les lignes
     * des roles concernes avant de les reposer.
     *
     * @param array<string, int> $roles
     */
    private function rattacherLesPermissions(array $roles): void
    {
        $permissions = DB::table('permissions')->pluck('id', 'name');

        if ($permissions->isEmpty()) {
            $this->command->warn('⚠  Aucune permission en base — lancez PermissionSeeder d’abord.');

            return;
        }

        DB::table('role_permissions')->whereIn('role_id', array_values($roles))->delete();

        $lignes = [];

        // L'administrateur porte toutes les permissions, presentes et a venir.
        foreach ($permissions as $permissionId) {
            $lignes[] = [
                'role_id' => $roles['admin'],
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (self::PERMISSIONS_PAR_ROLE as $nomRole => $nomsPermissions) {
            foreach ($nomsPermissions as $nomPermission) {
                if (! isset($permissions[$nomPermission])) {
                    continue;
                }

                $lignes[] = [
                    'role_id' => $roles[$nomRole],
                    'permission_id' => $permissions[$nomPermission],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('role_permissions')->insert($lignes);

        $this->command->info('✅ '.count($lignes).' permissions attribuées');
    }

    /**
     * Garantit un compte capable d'ouvrir le back-office.
     *
     * Le compte de demonstration existait deja, mais rattache au role
     * `employe` et sans `can_access_admin` : la connexion le renvoyait au
     * formulaire. On le repointe vers `admin` sans toucher a son mot de
     * passe s'il en a deja un.
     */
    private function promouvoirLAdministrateur(int $roleAdmin): void
    {
        $existant = DB::table('users')->where('email', 'admin@gmail.com')->first();

        $valeurs = [
            'first_name' => 'Admin',
            'last_name' => 'System',
            'role_id' => $roleAdmin,
            'is_active' => true,
            'can_access_admin' => true,
            'employee_type' => 'direction',
            'espace' => 'rh',
            'updated_at' => now(),
        ];

        // Un mot de passe deja pose est celui que connait l'utilisateur :
        // l'ecraser a chaque passage du seeder le deconnecterait.
        if (! $existant || ! filled($existant->password)) {
            $valeurs['password'] = Hash::make('admin123');
            $valeurs['created_at'] = now();
        }

        DB::table('users')->updateOrInsert(['email' => 'admin@gmail.com'], $valeurs);

        $this->command->info('✅ Administrateur : admin@gmail.com'
            .($existant && filled($existant->password) ? ' (mot de passe inchangé)' : ' / admin123'));
    }

    /**
     * Compte d'administration du transport.
     *
     * Il ouvre INSAM BUS et rien d'autre : `EspaceAutorise` le renvoie s'il
     * tente une page d'Estuaire RH. C'est le pendant de l'administrateur
     * RH, qui lui circule dans les deux espaces.
     */
    private function poserLAdministrateurTransport(int $roleAdminBus): void
    {
        $existant = DB::table('users')->where('email', 'bus@gmail.com')->first();

        $valeurs = [
            'first_name' => 'Admin',
            'last_name' => 'Transport',
            'role_id' => $roleAdminBus,
            'is_active' => true,
            'can_access_admin' => true,
            'employee_type' => 'administratif',
            // `espace = bus` decrit son perimetre reel ; le cloisonnement,
            // lui, tient au role et non a cette colonne.
            'espace' => 'bus',
            'updated_at' => now(),
        ];

        if (! $existant || ! filled($existant->password)) {
            $valeurs['password'] = Hash::make('bus123');
            $valeurs['created_at'] = now();
        }

        DB::table('users')->updateOrInsert(['email' => 'bus@gmail.com'], $valeurs);

        $this->command->info('✅ Administrateur transport : bus@gmail.com'
            .($existant && filled($existant->password) ? ' (mot de passe inchangé)' : ' / bus123'));
    }
}
