<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCpbdSubjects extends Command
{
    protected $signature = 'cpbd:import-subjects';
    protected $description = 'Importer les matieres du CPBD dans SystemRH (company_id=2)';

    protected array $groupNames = [
        'A' => 'Matieres Litteraires',
        'B' => 'Matieres Scientifiques',
        'C' => 'Matieres Pratiques',
        'D' => 'Autres Matieres',
        'E' => 'Matieres de Specialite',
    ];

    protected array $subjects = [
        ['name' => 'Orthographe', 'code' => 'Or', 'group' => 'A'],
        ['name' => 'Etude de Texte', 'code' => 'ET', 'group' => 'A'],
        ['name' => 'Anglais', 'code' => 'En', 'group' => 'A'],
        ['name' => 'Education de la Citoyennete', 'code' => 'ECM', 'group' => 'D'],
        ['name' => 'Espagnol', 'code' => 'EsP1', 'group' => 'A'],
        ['name' => 'Expression Orale', 'code' => 'EO', 'group' => 'A'],
        ['name' => 'Langue et Culture Nationale', 'code' => 'LCN', 'group' => 'A'],
        ['name' => 'Expression Ecrite', 'code' => 'EE', 'group' => 'A'],
        ['name' => 'Histoire', 'code' => 'Hist', 'group' => 'A'],
        ['name' => 'Geographie', 'code' => 'Geo', 'group' => 'A'],
        ['name' => 'Mathematiques', 'code' => 'Math', 'group' => 'B'],
        ['name' => 'Physique Chimie', 'code' => 'PC', 'group' => 'B'],
        ['name' => 'SVT', 'code' => 'svt', 'group' => 'B'],
        ['name' => 'Informatique', 'code' => 'inf', 'group' => 'C'],
        ['name' => 'Travail Manuel', 'code' => 'TM', 'group' => 'C'],
        ['name' => 'ESF', 'code' => 'esF', 'group' => 'E'],
        ['name' => 'Education Artistique', 'code' => 'EA', 'group' => 'C'],
        ['name' => 'Litterature', 'code' => 'Litt', 'group' => 'A'],
        ['name' => 'Langue Francaise', 'code' => 'LF', 'group' => 'A'],
        ['name' => 'Philosophie', 'code' => 'Philo', 'group' => 'A'],
        ['name' => 'Chimie', 'code' => 'chi', 'group' => 'B'],
        ['name' => 'Physique', 'code' => 'ph', 'group' => 'B'],
        ['name' => 'Allemand', 'code' => 'Adll', 'group' => 'A'],
        ['name' => 'TA/TAS', 'code' => 'TA', 'group' => 'E'],
        ['name' => 'OTA', 'code' => 'OTA', 'group' => 'E'],
        ['name' => 'Bureautique', 'code' => 'Bu', 'group' => 'E'],
        ['name' => 'Courrier (redaction prof)', 'code' => 'C', 'group' => 'E'],
        ['name' => 'Economie Generale', 'code' => 'EG', 'group' => 'E'],
        ['name' => 'Economie d\'Entreprises', 'code' => 'EOE', 'group' => 'E'],
        ['name' => 'Droit', 'code' => 'D', 'group' => 'D'],
        ['name' => 'MOB/PRP', 'code' => 'MP', 'group' => 'E'],
        ['name' => 'GSS', 'code' => 'GSS', 'group' => 'E'],
        ['name' => 'GRH', 'code' => 'GRH', 'group' => 'E'],
        ['name' => 'SPOS', 'code' => 'SPOS', 'group' => 'E'],
        ['name' => 'Action Sociale', 'code' => 'AS', 'group' => 'E'],
        ['name' => 'Labo', 'code' => 'Lbo', 'group' => 'E'],
        ['name' => 'Soins Infirmieres', 'code' => 'SI', 'group' => 'E'],
        ['name' => 'BC', 'code' => 'BC', 'group' => 'E'],
        ['name' => 'TC', 'code' => 'tc', 'group' => 'E'],
        ['name' => 'Finances D\'entreprises', 'code' => 'FE', 'group' => 'E'],
        ['name' => 'Compta de Management', 'code' => 'CM', 'group' => 'E'],
        ['name' => 'Compta d\'Entreprise', 'code' => 'CE', 'group' => 'E'],
        ['name' => 'GIF', 'code' => 'GIF', 'group' => 'E'],
        ['name' => 'Mathematiques appliquees', 'code' => 'Maths', 'group' => 'B'],
        ['name' => 'CGAO', 'code' => 'CGAO1', 'group' => 'E'],
        ['name' => 'GEH', 'code' => 'GEH', 'group' => 'E'],
        ['name' => 'Deontologie', 'code' => 'Deo', 'group' => 'E'],
        ['name' => 'Corresp', 'code' => 'Corresp', 'group' => 'E'],
        ['name' => 'Maths Commerciale', 'code' => 'Math com', 'group' => 'E'],
        ['name' => 'Commerce', 'code' => 'cmm', 'group' => 'E'],
        ['name' => 'DCC', 'code' => 'Dcc', 'group' => 'E'],
        ['name' => 'PRN', 'code' => 'Prn', 'group' => 'E'],
        ['name' => 'MOB', 'code' => 'Mob1', 'group' => 'E'],
        ['name' => 'Legislation', 'code' => 'legis', 'group' => 'D'],
        ['name' => 'Terminologies', 'code' => 'Ter', 'group' => 'E'],
        ['name' => 'PGD', 'code' => 'Pgd', 'group' => 'E'],
        ['name' => 'Nutrition', 'code' => 'Nu', 'group' => 'E'],
        ['name' => 'Technique Culinaire', 'code' => 'TC1', 'group' => 'E'],
        ['name' => 'PGO', 'code' => 'pgo', 'group' => 'E'],
        ['name' => 'EAO', 'code' => 'EAO', 'group' => 'E'],
        ['name' => 'GO', 'code' => 'go', 'group' => 'E'],
        ['name' => 'Hygiene', 'code' => 'hy', 'group' => 'B'],
        ['name' => 'SEL/AHMV', 'code' => 'SA', 'group' => 'E'],
        ['name' => 'Biologies', 'code' => 'B', 'group' => 'B'],
        ['name' => 'Vie Sociale', 'code' => 'VIE SOC.', 'group' => 'E'],
        ['name' => 'Marketing', 'code' => 'Mark', 'group' => 'E'],
        ['name' => 'HISTORY', 'code' => 'HIST10', 'group' => 'A'],
        ['name' => 'Hist-Geo', 'code' => 'H-G', 'group' => 'A'],
        ['name' => 'LOGIC', 'code' => 'LOG', 'group' => 'D'],
        ['name' => 'CITIZENSHIP', 'code' => 'CIT', 'group' => 'D'],
        ['name' => 'PHILOSOPHY', 'code' => 'PHI', 'group' => 'A'],
        ['name' => 'Physique-Chimie-Technologie', 'code' => 'PCT', 'group' => 'B'],
        ['name' => 'Physique Pratique', 'code' => 'P.P', 'group' => 'B'],
        ['name' => 'Chimie Pratique', 'code' => 'C.P', 'group' => 'B'],
        ['name' => 'Automatisme', 'code' => 'AU', 'group' => 'B'],
        ['name' => 'FRENCH', 'code' => 'FR', 'group' => 'A'],
        ['name' => 'Redaction Professionnelle', 'code' => 'RP', 'group' => 'E'],
        ['name' => 'PHYSICS', 'code' => 'PHY2', 'group' => 'B'],
        ['name' => 'MATHEMATICS', 'code' => 'MATHS1', 'group' => 'B'],
        ['name' => 'MATHS STATISTICS', 'code' => 'MATH STAT', 'group' => 'B'],
        ['name' => 'BIOLOGY', 'code' => 'BIO1', 'group' => 'B'],
        ['name' => 'SEL', 'code' => 'SEL', 'group' => 'E'],
        ['name' => 'AHMV', 'code' => 'AHMV', 'group' => 'E'],
        ['name' => 'EAD', 'code' => 'EAD', 'group' => 'D'],
        ['name' => 'Dessin Technique', 'code' => 'DT', 'group' => 'E'],
        ['name' => 'Dessin de Mode', 'code' => 'DM', 'group' => 'E'],
        ['name' => 'TP Fabrication', 'code' => 'TPF', 'group' => 'E'],
        ['name' => 'Couture', 'code' => 'COU', 'group' => 'E'],
        ['name' => 'Coupe', 'code' => 'COU1', 'group' => 'E'],
        ['name' => 'Metier et Formation', 'code' => 'MF', 'group' => 'E'],
        ['name' => 'Technologie', 'code' => 'TECHNO', 'group' => 'E'],
        ['name' => 'Organisation de Travail', 'code' => 'OT', 'group' => 'E'],
        ['name' => 'Patronnage Gradation', 'code' => 'PAT-GRADA', 'group' => 'E'],
        ['name' => 'Analyse', 'code' => 'ANAL', 'group' => 'E'],
        ['name' => 'Dessin d\'art', 'code' => 'DA', 'group' => 'E'],
        ['name' => 'Comptabilite', 'code' => 'COMPTA1', 'group' => 'E'],
        ['name' => 'TCFE', 'code' => 'TCFE1', 'group' => 'E'],
        ['name' => 'PCQ', 'code' => 'PCQ', 'group' => 'E'],
        ['name' => 'Statistique', 'code' => 'STAT', 'group' => 'B'],
        ['name' => 'Technique Commerciale', 'code' => 'TC2', 'group' => 'E'],
        ['name' => 'Entrepreneuriat', 'code' => 'ENTREP', 'group' => 'E'],
        ['name' => 'Sante Publique', 'code' => 'SP', 'group' => 'E'],
        ['name' => 'Biologie Physiopathologie Humaine', 'code' => 'BPPH', 'group' => 'E'],
        ['name' => 'Comptabilite Financiere', 'code' => 'CF', 'group' => 'E'],
        ['name' => 'Bureau commercial', 'code' => 'BC1', 'group' => 'E'],
        ['name' => 'GCAO', 'code' => 'GCAO', 'group' => 'E'],
        ['name' => 'Sciences', 'code' => 'SC', 'group' => 'B'],
        ['name' => 'HSE', 'code' => 'HSE', 'group' => 'E'],
        ['name' => 'Francais', 'code' => 'FRA', 'group' => 'A'],
        ['name' => 'Education Physique et Sportive', 'code' => 'EPS', 'group' => 'C'],
        ['name' => 'PHYSICAL TRAINING', 'code' => 'PT', 'group' => 'C'],
        ['name' => 'LITERATURE', 'code' => 'LIT', 'group' => 'A'],
        ['name' => 'CHEMISTRY', 'code' => 'CHEM', 'group' => 'B'],
        ['name' => 'FOOD AND NUTRITION', 'code' => 'FN', 'group' => 'E'],
        ['name' => 'FOOD SCIENCE', 'code' => 'FS', 'group' => 'E'],
        ['name' => 'COMPUTER SCIENCE', 'code' => 'CS', 'group' => 'C'],
        ['name' => 'ECONOMICS', 'code' => 'ECONS', 'group' => 'E'],
        ['name' => 'COMMERCE', 'code' => 'COMM1', 'group' => 'E'],
        ['name' => 'ADDITIONAL MATHS', 'code' => 'AD MATHS', 'group' => 'B'],
        ['name' => 'FURTHER MATHS', 'code' => 'F. MATHS', 'group' => 'B'],
        ['name' => 'GEOGRAPHY', 'code' => 'GEO2', 'group' => 'A'],
        ['name' => 'ENGLISH LANGUAGE', 'code' => 'ENG', 'group' => 'A'],
        ['name' => 'HUMAN BIOLOGY', 'code' => 'H/BIO', 'group' => 'B'],
        ['name' => 'ICT', 'code' => 'ICT', 'group' => 'C'],
    ];

    public function handle(): int
    {
        $companyId = 2;
        $annee = '2025-2026';
        $created = 0;
        $skipped = 0;

        // Ajouter 'matiere' a l'enum type_ue si pas encore fait
        try {
            DB::statement("ALTER TABLE unites_enseignement MODIFY COLUMN type_ue ENUM('specialite', 'tronc_commun', 'tronc_commun_general', 'tronc_commun_partiel', 'matiere') DEFAULT 'specialite'");
            $this->info('ENUM type_ue mis a jour avec "matiere".');
        } catch (\Exception $e) {
            $this->warn('ENUM deja a jour ou erreur: ' . $e->getMessage());
        }

        foreach ($this->subjects as $subject) {
            // Verifier doublon par code_ue + company_id
            $codeUe = 'CPBD-' . strtoupper($subject['code']);

            $exists = DB::table('unites_enseignement')
                ->where('company_id', $companyId)
                ->where('code_ue', $codeUe)
                ->exists();

            if ($exists) {
                $this->warn("SKIP (existe): {$codeUe} - {$subject['name']}");
                $skipped++;
                continue;
            }

            $groupName = $this->groupNames[$subject['group']] ?? $subject['group'];

            DB::table('unites_enseignement')->insert([
                'company_id' => $companyId,
                'code_ue' => $codeUe,
                'nom_matiere' => $subject['name'],
                'volume_horaire_total' => 0,
                'heures_effectuees_validees' => 0,
                'statut' => 'non_activee',
                'annee_academique' => $annee,
                'type_ue' => 'matiere',
                'specialite' => $groupName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->info("OK: {$codeUe} - {$subject['name']} ({$groupName})");
            $created++;
        }

        $this->newLine();
        $this->info("=== Matieres: {$created} creees, {$skipped} ignorees ===");

        return Command::SUCCESS;
    }
}
