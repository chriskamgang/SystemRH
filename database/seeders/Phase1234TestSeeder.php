<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class Phase1234TestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding des donnees de test Phases 1-4...');

        // Recuperer les users existants
        $admin = DB::table('users')->where('email', 'admin@gmail.com')->first();
        $jean = DB::table('users')->where('email', 'jean.mbongo@university.ga')->first();
        $marie = DB::table('users')->where('email', 'marie.okome@university.ga')->first();
        $paul = DB::table('users')->where('email', 'paul.ndong@university.ga')->first();
        $sophie = DB::table('users')->where('email', 'sophie.essono@university.ga')->first();
        $thomas = DB::table('users')->where('email', 'thomas.kamga@insam.cm')->first();
        $clarisse = DB::table('users')->where('email', 'clarisse.tchoumi@insam.cm')->first();

        if (!$admin || !$jean) {
            $this->command->error('Users de base non trouves. Lancez d\'abord: php artisan db:seed');
            return;
        }

        $deptInfo = DB::table('departments')->where('code', 'DEPT-INFO')->first();
        $deptArch = DB::table('departments')->where('code', 'DEPT-ARCH')->first();
        $deptMgmt = DB::table('departments')->where('code', 'DEPT-MGMT')->first();

        // =========================================================
        // PHASE 1 — Conges, Justificatifs, Pointages
        // =========================================================
        $this->command->info('--- Phase 1: Conges, Absences, Retards ---');

        // Conges pour Thomas
        if (DB::table('leave_requests')->count() === 0) {
            DB::table('leave_requests')->insert([
                [
                    'user_id' => $thomas->id,
                    'type' => 'annual',
                    'start_date' => Carbon::now()->addDays(10)->toDateString(),
                    'end_date' => Carbon::now()->addDays(15)->toDateString(),
                    'days_count' => 5,
                    'reason' => 'Vacances familiales au village',
                    'status' => 'pending',
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'user_id' => $jean->id,
                    'type' => 'sick',
                    'start_date' => Carbon::now()->subDays(5)->toDateString(),
                    'end_date' => Carbon::now()->subDays(3)->toDateString(),
                    'days_count' => 3,
                    'reason' => 'Grippe',
                    'status' => 'approved',
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'user_id' => $sophie ? $sophie->id : $thomas->id,
                    'type' => 'annual',
                    'start_date' => Carbon::now()->addDays(20)->toDateString(),
                    'end_date' => Carbon::now()->addDays(30)->toDateString(),
                    'days_count' => 10,
                    'reason' => 'Voyage personnel',
                    'status' => 'rejected',
                    'created_at' => now(), 'updated_at' => now(),
                ],
            ]);
            $this->command->info('  3 demandes de conge creees');
        }

        // Quelques attendances avec is_offline
        $campus = DB::table('campuses')->first();
        if ($campus && DB::table('attendances')->whereDate('timestamp', today())->count() === 0) {
            DB::table('attendances')->insert([
                [
                    'user_id' => $thomas->id,
                    'campus_id' => $campus->id,
                    'type' => 'check-in',
                    'timestamp' => Carbon::today()->setHour(7)->setMinute(55),
                    'latitude' => 5.4698,
                    'longitude' => 10.4189,
                    'status' => 'valid',
                    'is_late' => false,
                    'late_minutes' => 0,
                    'is_offline' => false,
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'user_id' => $thomas->id,
                    'campus_id' => $campus->id,
                    'type' => 'check-out',
                    'timestamp' => Carbon::today()->setHour(17)->setMinute(5),
                    'latitude' => 5.4698,
                    'longitude' => 10.4189,
                    'status' => 'valid',
                    'is_late' => false,
                    'late_minutes' => 0,
                    'is_offline' => false,
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'user_id' => $jean->id,
                    'campus_id' => $campus->id,
                    'type' => 'check-in',
                    'timestamp' => Carbon::today()->setHour(8)->setMinute(30),
                    'latitude' => 5.4700,
                    'longitude' => 10.4190,
                    'status' => 'valid',
                    'is_late' => true,
                    'late_minutes' => 30,
                    'is_offline' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ],
            ]);
            $this->command->info('  3 pointages crees (2 normal, 1 offline+retard)');
        }

        // =========================================================
        // PHASE 2 — Attestations, Messagerie, Fiches de paie
        // =========================================================
        $this->command->info('--- Phase 2: Attestations, Messagerie ---');

        // Attestations
        if (DB::table('work_certificates')->count() === 0) {
            DB::table('work_certificates')->insert([
                [
                    'user_id' => $thomas->id,
                    'type' => 'work',
                    'purpose' => 'Demande de visa',
                    'status' => 'pending',
                    'generated_by' => null,
                    'generated_at' => null,
                    'created_at' => now(), 'updated_at' => now(),
                ],
                [
                    'user_id' => $jean->id,
                    'type' => 'salary',
                    'purpose' => 'Demande de pret bancaire',
                    'status' => 'generated',
                    'generated_by' => $admin->id,
                    'generated_at' => now(),
                    'created_at' => now()->subDays(3), 'updated_at' => now(),
                ],
            ]);
            $this->command->info('  2 demandes d\'attestation creees');
        }

        // Conversations + messages
        if (DB::table('conversations')->count() === 0) {
            $convId = DB::table('conversations')->insertGetId([
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('conversation_participants')->insert([
                ['conversation_id' => $convId, 'user_id' => $thomas->id, 'created_at' => now(), 'updated_at' => now()],
                ['conversation_id' => $convId, 'user_id' => $jean->id, 'created_at' => now(), 'updated_at' => now()],
            ]);
            DB::table('messages')->insert([
                ['conversation_id' => $convId, 'sender_id' => $thomas->id, 'body' => 'Bonjour chef, j\'ai une question sur les UE.', 'created_at' => now()->subMinutes(30), 'updated_at' => now()],
                ['conversation_id' => $convId, 'sender_id' => $jean->id, 'body' => 'Oui Thomas, dis-moi.', 'created_at' => now()->subMinutes(25), 'updated_at' => now()],
                ['conversation_id' => $convId, 'sender_id' => $thomas->id, 'body' => 'Est-ce que je peux avoir le planning de la semaine prochaine ?', 'created_at' => now()->subMinutes(20), 'updated_at' => now()],
            ]);
            $this->command->info('  1 conversation avec 3 messages creee');
        }

        // Mettre a jour les profils (Phase 2 infos perso)
        DB::table('users')->where('id', $thomas->id)->update([
            'address' => 'Quartier Tamdja, Bafoussam',
            'emergency_contact_name' => 'Kamga Pierre',
            'emergency_contact_phone' => '+237 06 99 88 77',
            'banque' => 'Afriland First Bank',
            'numero_compte' => '00015 01001 0123456789 42',
            'monthly_salary' => 350000,
        ]);

        // Manager hierarchy
        DB::table('users')->where('id', $thomas->id)->update(['manager_id' => $jean->id]);
        DB::table('users')->where('id', $paul->id)->update(['manager_id' => $jean->id]);
        if ($sophie) DB::table('users')->where('id', $sophie->id)->update(['manager_id' => $admin->id]);
        if ($clarisse) DB::table('users')->where('id', $clarisse->id)->update(['manager_id' => $jean->id]);

        // Head of departments
        if ($deptInfo) DB::table('departments')->where('id', $deptInfo->id)->update(['head_user_id' => $jean->id]);
        if ($deptArch) DB::table('departments')->where('id', $deptArch->id)->update(['head_user_id' => $marie->id]);

        // =========================================================
        // PHASE 3 — Evaluations, CNPS, Onboarding
        // =========================================================
        $this->command->info('--- Phase 3: Evaluations, CNPS, Onboarding ---');

        // Evaluation Campaign + Criteria
        if (DB::table('evaluation_campaigns')->count() === 0) {
            $campaignId = DB::table('evaluation_campaigns')->insertGetId([
                'title' => 'Evaluation annuelle 2025-2026',
                'year' => 2026,
                'start_date' => Carbon::now()->subMonths(1)->toDateString(),
                'end_date' => Carbon::now()->addMonths(1)->toDateString(),
                'status' => 'active',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $criteriaIds = [];
            $criteria = [
                ['name' => 'Competences techniques', 'description' => 'Maitrise des outils et methodes', 'max_score' => 5, 'weight' => 3],
                ['name' => 'Communication', 'description' => 'Capacite a communiquer avec l\'equipe', 'max_score' => 5, 'weight' => 2],
                ['name' => 'Ponctualite', 'description' => 'Respect des horaires et delais', 'max_score' => 5, 'weight' => 2],
                ['name' => 'Initiative', 'description' => 'Prise d\'initiative et proactivite', 'max_score' => 5, 'weight' => 1],
                ['name' => 'Travail en equipe', 'description' => 'Collaboration avec les collegues', 'max_score' => 5, 'weight' => 2],
            ];
            foreach ($criteria as $c) {
                $criteriaIds[] = DB::table('evaluation_criteria')->insertGetId(array_merge($c, [
                    'campaign_id' => $campaignId,
                    'created_at' => now(), 'updated_at' => now(),
                ]));
            }

            // Evaluation pour Thomas (pending - peut faire auto-eval)
            $evalThomasId = DB::table('evaluations')->insertGetId([
                'campaign_id' => $campaignId,
                'employee_id' => $thomas->id,
                'evaluator_id' => $jean->id,
                'status' => 'pending',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // Evaluation pour Jean (self_evaluated)
            $evalJeanId = DB::table('evaluations')->insertGetId([
                'campaign_id' => $campaignId,
                'employee_id' => $jean->id,
                'evaluator_id' => $admin->id,
                'status' => 'self_evaluated',
                'employee_comments' => 'J\'ai fait de mon mieux cette annee.',
                'self_evaluated_at' => now()->subDays(5),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            // Scores auto-eval pour Jean
            $jeanScores = [4, 3, 5, 4, 4];
            foreach ($criteriaIds as $i => $cid) {
                DB::table('evaluation_scores')->insert([
                    'evaluation_id' => $evalJeanId,
                    'criteria_id' => $cid,
                    'employee_score' => $jeanScores[$i],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            // Evaluation pour Clarisse (evaluated - complete)
            if ($clarisse) {
                $evalClarisseId = DB::table('evaluations')->insertGetId([
                    'campaign_id' => $campaignId,
                    'employee_id' => $clarisse->id,
                    'evaluator_id' => $jean->id,
                    'status' => 'evaluated',
                    'overall_score' => 3.8,
                    'employee_comments' => 'Annee productive.',
                    'evaluator_comments' => 'Bonne performance globale.',
                    'objectives_next_year' => 'Ameliorer les competences en gestion de projet.',
                    'training_needs' => 'Formation Excel avancee, Management.',
                    'self_evaluated_at' => now()->subDays(10),
                    'evaluated_at' => now()->subDays(3),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $clarisseScores = [4, 3, 4, 3, 4];
                $evalScores = [4, 4, 4, 3, 4];
                foreach ($criteriaIds as $i => $cid) {
                    DB::table('evaluation_scores')->insert([
                        'evaluation_id' => $evalClarisseId,
                        'criteria_id' => $cid,
                        'employee_score' => $clarisseScores[$i],
                        'evaluator_score' => $evalScores[$i],
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            $this->command->info('  1 campagne, 5 criteres, 3 evaluations creees');
        }

        // CNPS records + contributions
        if (DB::table('cnps_records')->count() === 0) {
            foreach ([$thomas, $jean, $clarisse] as $user) {
                if (!$user) continue;
                DB::table('cnps_records')->insert([
                    'user_id' => $user->id,
                    'cnps_number' => 'CNPS-' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                    'registration_date' => Carbon::now()->subYears(2)->toDateString(),
                    'status' => 'active',
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                // 4 mois de cotisations (Jan-Avr 2026)
                for ($month = 1; $month <= 4; $month++) {
                    $gross = 350000;
                    $employeeContrib = round($gross * 0.042);
                    $employerContrib = round($gross * 0.1015);
                    DB::table('cnps_contributions')->insert([
                        'user_id' => $user->id,
                        'year' => 2026,
                        'month' => $month,
                        'gross_salary' => $gross,
                        'employee_contribution' => $employeeContrib,
                        'employer_contribution' => $employerContrib,
                        'total_contribution' => $employeeContrib + $employerContrib,
                        'status' => $month <= 3 ? 'paid' : 'calculated',
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
            $this->command->info('  3 dossiers CNPS + 12 cotisations creees');
        }

        // Onboarding
        if (DB::table('onboarding_templates')->count() === 0) {
            // Template onboarding
            $tplOnboardId = DB::table('onboarding_templates')->insertGetId([
                'name' => 'Integration nouvel employe INSAM',
                'type' => 'onboarding',
                'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $tplTasks = [
                ['title' => 'Signer le contrat de travail', 'assigned_to' => 'hr', 'sort_order' => 1],
                ['title' => 'Creer le compte email professionnel', 'assigned_to' => 'it', 'sort_order' => 2],
                ['title' => 'Configurer le poste de travail', 'assigned_to' => 'it', 'sort_order' => 3],
                ['title' => 'Visite des locaux', 'assigned_to' => 'manager', 'sort_order' => 4],
                ['title' => 'Lire le reglement interieur', 'assigned_to' => 'employee', 'sort_order' => 5],
                ['title' => 'Remplir la fiche d\'information personnelle', 'assigned_to' => 'employee', 'sort_order' => 6],
                ['title' => 'Formation securite incendie', 'assigned_to' => 'employee', 'sort_order' => 7],
                ['title' => 'Inscription CNPS', 'assigned_to' => 'hr', 'sort_order' => 8],
            ];
            foreach ($tplTasks as $t) {
                DB::table('onboarding_template_tasks')->insert(array_merge($t, [
                    'template_id' => $tplOnboardId,
                    'created_at' => now(), 'updated_at' => now(),
                ]));
            }

            // Template offboarding
            $tplOffboardId = DB::table('onboarding_templates')->insertGetId([
                'name' => 'Depart employe',
                'type' => 'offboarding',
                'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // Processus onboarding pour Thomas (en cours)
            $processId = DB::table('onboarding_processes')->insertGetId([
                'template_id' => $tplOnboardId,
                'user_id' => $thomas->id,
                'type' => 'onboarding',
                'status' => 'in_progress',
                'start_date' => Carbon::now()->subDays(15)->toDateString(),
                'target_date' => Carbon::now()->addDays(15)->toDateString(),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // Creer les taches du processus (certaines completees, d'autres non)
            $taskStatuses = ['completed', 'completed', 'completed', 'completed', 'pending', 'pending', 'pending', 'pending'];
            foreach ($tplTasks as $i => $t) {
                DB::table('onboarding_tasks')->insert([
                    'process_id' => $processId,
                    'title' => $t['title'],
                    'assigned_to' => $t['assigned_to'],
                    'sort_order' => $t['sort_order'],
                    'status' => $taskStatuses[$i],
                    'due_date' => Carbon::now()->addDays($i * 2)->toDateString(),
                    'completed_date' => $taskStatuses[$i] === 'completed' ? Carbon::now()->subDays(10 - $i)->toDateString() : null,
                    'completed_by' => $taskStatuses[$i] === 'completed' ? $admin->id : null,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $this->command->info('  2 templates onboarding, 1 processus avec 8 taches cree');
        }

        // =========================================================
        // PHASE 4 — Recrutement, Formation, Analytics
        // =========================================================
        $this->command->info('--- Phase 4: Recrutement, Formation, Analytics ---');

        // Job postings
        if (DB::table('job_postings')->count() === 0) {
            $postingIds = [];

            $postingIds[] = DB::table('job_postings')->insertGetId([
                'title' => 'Enseignant en Informatique',
                'description' => 'L\'INSAM recherche un enseignant en informatique pour les cours de programmation et base de donnees. Poste a plein temps avec possibilite d\'evolution.',
                'department_id' => $deptInfo?->id,
                'location' => 'Bafoussam',
                'contract_type' => 'cdi',
                'salary_range' => '300 000 - 500 000 FCFA',
                'requirements' => "- Master en Informatique minimum\n- 3 ans d'experience en enseignement\n- Maitrise de Java, Python, SQL\n- Bonne pedagogie",
                'responsibilities' => "- Dispenser les cours d'informatique\n- Elaborer les supports de cours\n- Encadrer les projets etudiants\n- Participer aux jurys d'examen",
                'benefits' => "- Mutuelle sante\n- Prime de transport\n- Formation continue\n- 6 semaines de conge",
                'status' => 'published',
                'positions_count' => 2,
                'published_at' => now()->subDays(5),
                'closes_at' => now()->addDays(25),
                'created_by' => $admin->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $postingIds[] = DB::table('job_postings')->insertGetId([
                'title' => 'Assistant Administratif',
                'description' => 'Nous recherchons un(e) assistant(e) administratif(ve) pour le departement de gestion.',
                'department_id' => $deptMgmt?->id,
                'location' => 'Bafoussam',
                'contract_type' => 'cdd',
                'salary_range' => '150 000 - 250 000 FCFA',
                'requirements' => "- BTS en Secretariat/Gestion minimum\n- Maitrise de MS Office\n- Sens de l'organisation",
                'status' => 'published',
                'positions_count' => 1,
                'published_at' => now()->subDays(10),
                'closes_at' => now()->addDays(20),
                'created_by' => $admin->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $postingIds[] = DB::table('job_postings')->insertGetId([
                'title' => 'Vacataire en Architecture',
                'description' => 'Recherche d\'un vacataire pour les cours de dessin technique et DAO.',
                'department_id' => $deptArch?->id,
                'location' => 'Bafoussam',
                'contract_type' => 'vacataire',
                'salary_range' => '5 000 FCFA/heure',
                'status' => 'published',
                'positions_count' => 3,
                'published_at' => now()->subDays(2),
                'closes_at' => now()->addMonths(2),
                'created_by' => $admin->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // Candidatures sur la premiere offre
            $applicationStatuses = ['new', 'screening', 'interview', 'rejected', 'new'];
            $candidates = [
                ['name' => 'Alain FOTSO', 'email' => 'alain.fotso@email.com', 'phone' => '+237 06 11 22 33'],
                ['name' => 'Berthe MEKONG', 'email' => 'berthe.mekong@email.com', 'phone' => '+237 06 44 55 66'],
                ['name' => 'Charles NKWI', 'email' => 'charles.nkwi@email.com', 'phone' => '+237 06 77 88 99'],
                ['name' => 'Diane TAMO', 'email' => 'diane.tamo@email.com', 'phone' => '+237 06 22 33 44'],
                ['name' => 'Emmanuel DJOMO', 'email' => 'emmanuel.djomo@email.com', 'phone' => '+237 06 55 66 77'],
            ];
            foreach ($candidates as $i => $c) {
                DB::table('job_applications')->insert([
                    'job_posting_id' => $postingIds[0],
                    'candidate_name' => $c['name'],
                    'candidate_email' => $c['email'],
                    'candidate_phone' => $c['phone'],
                    'cover_letter' => 'Motivee(e) par cette offre, je souhaite mettre mes competences au service de l\'INSAM.',
                    'status' => $applicationStatuses[$i],
                    'rating' => $applicationStatuses[$i] === 'interview' ? 4 : ($applicationStatuses[$i] === 'screening' ? 3 : null),
                    'interview_date' => $applicationStatuses[$i] === 'interview' ? now()->addDays(3) : null,
                    'created_at' => now()->subDays(5 - $i), 'updated_at' => now(),
                ]);
            }

            $this->command->info('  3 offres d\'emploi + 5 candidatures creees');
        }

        // Training programs
        if (DB::table('training_programs')->count() === 0) {
            // Programme 1: Excel
            $prog1 = DB::table('training_programs')->insertGetId([
                'title' => 'Excel Avance pour la Gestion RH',
                'description' => 'Maitrisez les tableaux croises dynamiques, les macros VBA et les formules avancees pour optimiser votre travail quotidien.',
                'type' => 'online',
                'category' => 'Bureautique',
                'duration_hours' => 20,
                'level' => 'intermediate',
                'is_mandatory' => false,
                'is_active' => true,
                'created_by' => $admin->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $mat1a = DB::table('training_materials')->insertGetId([
                'training_program_id' => $prog1, 'title' => 'Introduction aux TCD', 'type' => 'video',
                'duration_minutes' => 45, 'sort_order' => 1, 'is_required' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $mat1b = DB::table('training_materials')->insertGetId([
                'training_program_id' => $prog1, 'title' => 'Exercices TCD', 'type' => 'pdf',
                'duration_minutes' => 30, 'sort_order' => 2, 'is_required' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $mat1c = DB::table('training_materials')->insertGetId([
                'training_program_id' => $prog1, 'title' => 'Quiz: Tableaux Croises', 'type' => 'quiz',
                'duration_minutes' => 15, 'sort_order' => 3, 'is_required' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('training_materials')->insert([
                'training_program_id' => $prog1, 'title' => 'Macros VBA - Initiation', 'type' => 'video',
                'duration_minutes' => 60, 'sort_order' => 4, 'is_required' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('training_materials')->insert([
                'training_program_id' => $prog1, 'title' => 'Ressources supplementaires', 'type' => 'link',
                'external_url' => 'https://support.microsoft.com/excel', 'sort_order' => 5, 'is_required' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // Inscription Thomas (en cours, 50%)
            DB::table('training_enrollments')->insert([
                'user_id' => $thomas->id, 'training_program_id' => $prog1,
                'status' => 'in_progress', 'progress' => 50,
                'started_at' => now()->subDays(7),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('training_material_progress')->insert([
                ['user_id' => $thomas->id, 'training_material_id' => $mat1a, 'is_completed' => true, 'completed_at' => now()->subDays(5), 'created_at' => now(), 'updated_at' => now()],
                ['user_id' => $thomas->id, 'training_material_id' => $mat1b, 'is_completed' => true, 'completed_at' => now()->subDays(3), 'created_at' => now(), 'updated_at' => now()],
            ]);

            // Programme 2: Securite incendie (obligatoire, presentiel)
            $prog2 = DB::table('training_programs')->insertGetId([
                'title' => 'Formation Securite Incendie',
                'description' => 'Formation obligatoire sur les procedures d\'evacuation et l\'utilisation des extincteurs.',
                'type' => 'presential',
                'category' => 'Securite',
                'duration_hours' => 4,
                'level' => 'beginner',
                'is_mandatory' => true,
                'is_active' => true,
                'created_by' => $admin->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            DB::table('training_sessions')->insert([
                'training_program_id' => $prog2,
                'trainer_name' => 'Capitaine Mbarga (Sapeurs-Pompiers)',
                'location' => 'Campus INSAM - Salle polyvalente',
                'start_date' => now()->addDays(7)->setHour(9),
                'end_date' => now()->addDays(7)->setHour(13),
                'max_participants' => 30,
                'status' => 'scheduled',
                'created_at' => now(), 'updated_at' => now(),
            ]);

            DB::table('training_materials')->insert([
                ['training_program_id' => $prog2, 'title' => 'Guide evacuation PDF', 'type' => 'pdf', 'sort_order' => 1, 'is_required' => true, 'created_at' => now(), 'updated_at' => now()],
                ['training_program_id' => $prog2, 'title' => 'Quiz securite', 'type' => 'quiz', 'sort_order' => 2, 'is_required' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);

            // Programme 3: Pedagogie (hybride)
            $prog3 = DB::table('training_programs')->insertGetId([
                'title' => 'Techniques Pedagogiques Modernes',
                'description' => 'Ameliorez vos methodes d\'enseignement avec les outils numeriques et la pedagogie active.',
                'type' => 'hybrid',
                'category' => 'Pedagogie',
                'duration_hours' => 30,
                'level' => 'advanced',
                'is_mandatory' => false,
                'is_active' => true,
                'created_by' => $admin->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // Inscription Jean (completed)
            DB::table('training_enrollments')->insert([
                'user_id' => $jean->id, 'training_program_id' => $prog3,
                'status' => 'completed', 'progress' => 100, 'score' => 85.50,
                'started_at' => now()->subMonths(2),
                'completed_at' => now()->subDays(10),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $this->command->info('  3 programmes de formation, 2 inscriptions, 7 materiaux crees');
        }

        // HR Analytics Snapshots (6 derniers mois)
        if (DB::table('hr_analytics_snapshots')->count() === 0) {
            $baseEmployees = 35;
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $total = $baseEmployees + (5 - $i) * 2;
                DB::table('hr_analytics_snapshots')->insert([
                    'year' => $date->year,
                    'month' => $date->month,
                    'total_employees' => $total,
                    'new_hires' => rand(1, 4),
                    'departures' => rand(0, 2),
                    'turnover_rate' => round(rand(2, 8) / 100 * 100, 2),
                    'avg_attendance_rate' => round(rand(82, 96) + rand(0, 99) / 100, 2),
                    'avg_late_rate' => round(rand(5, 18) + rand(0, 99) / 100, 2),
                    'total_leave_days' => rand(15, 45),
                    'total_payroll' => $total * 350000,
                    'avg_evaluation_score' => round(3 + rand(0, 200) / 100, 2),
                    'training_completions' => rand(2, 10),
                    'open_positions' => rand(1, 5),
                    'department_breakdown' => json_encode(['Informatique' => rand(8, 12), 'Architecture' => rand(6, 10), 'Gestion' => rand(5, 8)]),
                    'employee_type_breakdown' => json_encode(['permanent' => rand(15, 20), 'vacataire' => rand(8, 12), 'semi_permanent' => rand(5, 8)]),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $this->command->info('  6 snapshots analytics mensuels crees');
        }

        $this->command->info('');
        $this->command->info('=== SEEDING TERMINE ===');
        $this->command->info('');
        $this->command->info('Comptes de test:');
        $this->command->info('  admin@gmail.com / admin123 (Admin - voit tout)');
        $this->command->info('  thomas.kamga@insam.cm / password123 (Employe permanent - a des evaluations, onboarding, CNPS, formations)');
        $this->command->info('  jean.mbongo@university.ga / password123 (Chef dept - manager, evaluateur)');
        $this->command->info('  clarisse.tchoumi@insam.cm / password123 (Semi-permanent - evaluation completee)');
    }
}
