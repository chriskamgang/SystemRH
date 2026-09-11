<?php

namespace Database\Seeders;

use App\Enums\RoleUtilisateur;
use App\Enums\SensParcours;
use App\Enums\TypeLieu;
use App\Models\Chauffeur;
use App\Models\EtapeParcours;
use App\Models\Lieu;
use App\Models\Ligne;
use App\Models\Parcours;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Reseau de ramassage reel INSAM Bafoussam, releve sur les feuilles de
 * service manuscrites des chauffeurs (dossier `lines/`, 7 photos).
 *
 * Chaque feuille donne, pour une ligne : le chauffeur et son telephone,
 * puis la liste ordonnee des stationnements avec leur horaire de passage.
 * Les horaires sont notes « 6h10 - 6h15 / 7h15 » : la plage est l'arret
 * du premier tour (arrivee - depart), le nombre isole apres la barre est
 * le passage du second tour. On ne retient ici que l'heure de DEPART du
 * premier tour, seule valeur qui ordonne le parcours ; la duree entre
 * deux etapes est calculee depuis ces heures (`minutes_depuis_depart`).
 *
 * Les coordonnees GPS ne figurent pas sur les feuilles : elles ont ete
 * recherchees a partir des quartiers de Bafoussam. Celles marquees
 * `gps_a_verifier` sont des estimations de quartier, a relever sur le
 * terrain avant d'activer la validation geographique des pointages.
 *
 * Le seeder est idempotent : relance sans creer de doublon.
 */
class ReseauBusInsamSeeder extends Seeder
{
    /**
     * Lieux desservis : nom => [latitude, longitude, adresse, precis?].
     *
     * `precis = false` signale une position deduite du quartier, pas un
     * releve : le rayon de validation reste large tant qu'elle n'est pas
     * confirmee.
     */
    private const LIEUX = [
        // --- Axe Mairie rurale / Ecole Normale / Marche B (nord-ouest) ---
        // Tronc commun des cinq feuilles Kamkop : le bus descend du
        // nord-ouest vers le centre, puis rejoint le campus a l'est.
        'Mairie rurale' => [5.4971, 10.3969, 'Carrefour Mairie Rurale', true],
        'Tankou' => [5.4956, 10.3992, 'Carrefour Tankou, station MRS', true],
        'Entrée Domicile Tankou' => [5.4954, 10.3997, 'Quartier Tankou, entrée domicile', false],
        'Entrée École Normale' => [5.4896, 10.4042, 'Entrée École Normale', true],
        'Marché B' => [5.4863, 10.4091, 'Carrefour Marché B', true],
        'Feu rouge' => [5.4831, 10.4117, 'Carrefour Feu Rouge', true],

        // --- Axe Kamkop (nord-ouest), en amont de la Mairie rurale ---
        // « Tradex » est ambigu : deux stations portent ce nom, l'une a
        // Kamkop, l'autre sur l'axe Bandjoun. La ligne partant de Kamkop,
        // c'est celle de Kamkop qui est retenue.
        'Tradex' => [5.5017, 10.3859, 'Station Tradex Kamkop', false],
        'Palace' => [5.4978, 10.3907, 'Entrée du Palais Kamkop', false],
        'Ta\'aba' => [5.5050, 10.3830, 'Quartier Ta\'aba', false],
        'Ancien Dépôt Guinness' => [5.4990, 10.3890, 'Ancien dépôt Guinness', false],

        // Une station CAMOCO est cartographiee a l'est, pres du Campus C,
        // mais la feuille Oumarou enchaine Camoco -> Tradex en 5 minutes :
        // impossible sur 7 km. L'arret est donc un homonyme de l'axe
        // Kamkop, positionne ici juste en amont de Tradex.
        'Camoco' => [5.5035, 10.3845, 'Camoco, axe Kamkop', false],

        // --- Axe Tougang (nord) ---
        'Stade de Tougang' => [5.4885, 10.4200, 'Stade de Tougang', false],

        // --- Axe SOCADA / Explosif (sud-ouest) ---
        'Carrefour Explosif' => [5.4668, 10.4010, 'Carrefour Explosif', true],
        'Carrefour Saint Thomas' => [5.4702, 10.4040, 'Entrée Collège Saint Thomas', true],
        'Carrefour Socada' => [5.4649, 10.4080, 'Carrefour Socada', true],
        'Finance' => [5.4708, 10.4193, 'Hôtel des Finances de Bafoussam', true],

        // --- Axe Bandjoun (sud), de Bandjoun vers l'entree de ville ---
        // Bandjoun est a une douzaine de kilometres au sud : les premiers
        // points sont hors agglomeration, sur la nationale.
        'Tobou' => [5.4000, 10.4150, 'Tobou, axe Bandjoun (nationale)', false],
        'Mboo' => [5.4345, 10.4229, 'Mbouo, axe Bandjoun', false],
        'Ancien Centre Climatique' => [5.4450, 10.4240, 'Ancien centre climatique', false],
        'INO' => [5.4490, 10.4260, 'INO, axe Bandjoun', false],
        'Entrée de la ville' => [5.4520, 10.4280, 'Entrée de la ville de Bafoussam', false],
        'Carrefour Nzamengou' => [5.4600, 10.4250, 'Carrefour Nzamengou', false],

        // --- Axe Tamdja / Eveche (approche du campus) ---
        'Total Tamdja' => [5.4689, 10.4223, 'Station Total, carrefour Tamdja', true],
        'Stade municipal' => [5.4743, 10.4278, 'Stade municipal de Bamendzi', false],
        // L'Eveche est desservi plusieurs fois dans le tour : un seul
        // lieu physique, les passages successifs se lisent dans l'ordre
        // des etapes.
        'Éveché' => [5.4728, 10.4300, 'Éveché de Bafoussam', false],
        'Tabot d’en haut' => [5.4680, 10.4265, 'Tabot d’en haut', false],
        'Carrefour Armée' => [5.4710, 10.4280, 'Carrefour Armée', false],
    ];

    /**
     * Les neuf feuilles de service.
     *
     * `etapes` : [nom du lieu, heure de depart du premier tour]. Le
     * dernier element est le terminus (campus), qui clot le tour.
     */
    private const LIGNES = [
        [
            'code' => 'B-BANDJOUN',
            'nom' => 'Bandjoun — Campus C',
            'chauffeurs' => [
                ['nom' => 'MAPITO', 'tel' => '690668217'],
                ['nom' => 'Joseph', 'tel' => '655454998'],
            ],
            'etapes' => [
                ['Tobou', '06:40'],
                ['Mboo', '06:50'],
                ['Ancien Centre Climatique', '06:55'],
                ['INO', '07:00'],
                ['Entrée de la ville', '07:10'],
                ['Carrefour Nzamengou', '07:15'],
                ['Total Tamdja', '07:20'],
                ['Stade municipal', '07:25'],
                ['Éveché', '07:30'],
                // La feuille note 7h25 pour le 2e passage a l'Eveche, soit
                // avant le 1er : incoherence du releve, conservee telle
                // quelle. L'ordre du parcours vient du rang de la ligne,
                // pas de l'heure, donc le trajet reste correct.
                ['Éveché', '07:25'],
                ['Campus C', '07:45'],
            ],
        ],
        [
            'code' => 'B-KAMKOP-1',
            'nom' => 'Kamkop (Kabirou) — Campus C',
            'chauffeurs' => [['nom' => 'KABIROU', 'tel' => '692958111']],
            'etapes' => [
                ['Mairie rurale', '06:15'],
                ['Entrée Domicile Tankou', '06:20'],
                ['Entrée École Normale', '06:25'],
                ['Marché B', '06:35'],
                ['Feu rouge', '06:40'],
                ['Campus C', '06:55'],
            ],
        ],
        [
            'code' => 'B-KAMKOP-2',
            'nom' => 'Kamkop (Willy) — Campus C',
            'chauffeurs' => [['nom' => 'WILLY', 'tel' => '697243174']],
            'etapes' => [
                ['Mairie rurale', '06:15'],
                ['Entrée École Normale', '06:25'],
                ['Marché B', '06:30'],
                ['Feu rouge', '06:30'],
                ['Campus C', '06:50'],
            ],
        ],
        [
            'code' => 'B-KAMKOP-TOUGANG',
            'nom' => 'Kamkop — Tougang — Campus C',
            'chauffeurs' => [['nom' => 'GERAD', 'tel' => '699967091']],
            'etapes' => [
                ['Mairie rurale', '06:15'],
                ['Tankou', '06:25'],
                ['Entrée École Normale', '06:30'],
                ['Marché B', '06:35'],
                ['Feu rouge', '06:40'],
                // Le bus dessert le campus, pousse jusqu'au stade de
                // Tougang puis revient : le campus figure deux fois, et
                // seul le second passage clot le tour.
                ['Campus C', '07:00'],
                ['Stade de Tougang', '07:15'],
                ['Campus C', '07:35'],
            ],
        ],
        [
            'code' => 'B-KAMKOP-3',
            'nom' => 'Kamkop (Bertrand) — Campus C',
            'chauffeurs' => [['nom' => 'Bertrand', 'tel' => '653909603']],
            'etapes' => [
                ['Mairie rurale', '06:15'],
                ['Tankou', '06:25'],
                ['Entrée École Normale', '06:30'],
                ['Marché B', '06:35'],
                ['Feu rouge', '06:40'],
                ['Campus C', '07:00'],
            ],
        ],
        [
            'code' => 'B-ENTREE-VILLE',
            'nom' => 'Entrée de la ville — Campus C',
            'chauffeurs' => [['nom' => 'ROCK', 'tel' => '698868899']],
            // « Tabot d'en haut » ouvrait la feuille puis a ete barre au
            // profit de « Total Tamdja » : on suit la correction du
            // chauffeur. Le lieu reste en fin de parcours, ou il sert de
            // point de depose apres le campus.
            'etapes' => [
                ['Total Tamdja', '06:30'],
                ['Carrefour Armée', '06:30'],
                ['Éveché', '06:40'],
                ['Campus C', '06:55'],
                ['Tabot d’en haut', '07:10'],
            ],
        ],
        [
            'code' => 'B-SOCADA',
            'nom' => 'SOCADA — Explosif — Campus C',
            'chauffeurs' => [['nom' => 'FOTSO', 'tel' => '695457290']],
            'etapes' => [
                ['Carrefour Explosif', '06:10'],
                ['Carrefour Saint Thomas', '06:20'],
                ['Carrefour Socada', '06:30'],
                ['Finance', '06:40'],
                ['Campus C', '06:55'],
            ],
        ],
        [
            'code' => 'B-KAMKOP-4',
            'nom' => 'Kamkop (Oumarou) — Campus C',
            'chauffeurs' => [['nom' => 'OUMAROU', 'tel' => '678884757']],
            'etapes' => [
                ['Camoco', '06:20'],
                ['Tradex', '06:25'],
                ['Palace', '06:30'],
                // « Entree Ecole Normale » avait ete ecrit ici puis barre
                // au profit de « Mairie rurale » ; l'ecole reste desservie
                // dix minutes plus tard.
                ['Mairie rurale', '06:40'],
                ['Entrée École Normale', '06:50'],
                ['Marché B', '06:55'],
                ['Feu rouge', '07:00'],
                ['Campus C', '07:20'],
            ],
        ],
        [
            'code' => 'B-KAMKOP-5',
            'nom' => 'Kamkop (Nsim) — Campus C',
            'chauffeurs' => [['nom' => 'NSIM', 'tel' => '678170820']],
            'etapes' => [
                ['Ta\'aba', '06:15'],
                ['Ancien Dépôt Guinness', '06:20'],
                ['Mairie rurale', '06:25'],
                ['Entrée École Normale', '06:30'],
                ['Marché B', '06:40'],
                ['Feu rouge', '06:45'],
                ['Campus C', '07:00'],
            ],
        ],
    ];

    /**
     * Code de connexion attribue a tout chauffeur cree par ce seeder.
     *
     * Le cast `hashed` du modele le chiffre a l'enregistrement. C'est un
     * code d'amorcage, identique pour tous : a faire changer des la
     * premiere connexion.
     */
    private const PIN_PAR_DEFAUT = '0000';

    public function run(): void
    {
        $lieux = $this->creerLieux();

        foreach (self::LIGNES as $feuille) {
            $ligne = $this->creerLigne($feuille);
            $this->creerParcours($ligne, $feuille, $lieux);
            $this->creerChauffeurs($feuille);
        }
    }

    /** @return array<string, Lieu> nom du lieu => modele */
    private function creerLieux(): array
    {
        $lieux = [];

        foreach (self::LIEUX as $nom => [$lat, $lng, $adresse, $precis]) {
            $lieux[$nom] = Lieu::updateOrCreate(
                ['nom' => $nom],
                [
                    'adresse' => $precis ? $adresse : "{$adresse} (GPS à vérifier)",
                    'type' => TypeLieu::Ramassage,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    // Tant que la position n'est qu'une estimation de
                    // quartier, un rayon serre rejetterait des pointages
                    // legitimes : on l'elargit jusqu'au releve terrain.
                    'rayon_validation_metres' => $precis ? 150 : 500,
                    'actif' => true,
                ]
            );
        }

        // Campus C existe deja en base : on le reutilise plutot que d'en
        // creer un doublon sous un autre jeu de coordonnees.
        $campus = Lieu::where('type', TypeLieu::Campus->value)
            ->where('nom', 'Campus C')
            ->first();

        if ($campus) {
            $lieux['Campus C'] = $campus;
        }

        return $lieux;
    }

    private function creerLigne(array $feuille): Ligne
    {
        $etapes = $feuille['etapes'];
        $duree = $this->minutesEntre($etapes[0][1], end($etapes)[1]);

        $contacts = collect($feuille['chauffeurs'])
            ->map(fn (array $c) => "{$c['nom']} ({$this->formatTelephone($c['tel'])})")
            ->implode(', ');

        return Ligne::updateOrCreate(
            ['code' => $feuille['code']],
            [
                'nom' => $feuille['nom'],
                'description' => "Relevé sur feuille de service manuscrite. Chauffeur(s) : {$contacts}.",
                'duree_trajet_minutes' => max($duree, 1),
                'tours_prevus_par_jour' => 2,
                'actif' => true,
            ]
        );
    }

    private function creerParcours(Ligne $ligne, array $feuille, array $lieux): void
    {
        $parcours = Parcours::updateOrCreate(
            ['ligne_id' => $ligne->id, 'sens' => SensParcours::Aller->value],
            [
                'libelle' => "{$ligne->nom} — aller",
                'heure_depart' => $feuille['etapes'][0][1],
                'duree_reference_minutes' => $ligne->duree_trajet_minutes,
                'actif' => true,
            ]
        );

        // Une relance peut raccourcir un parcours : on repart d'une table
        // vide pour que `ordre` reste contigu et sans etape orpheline.
        $parcours->etapes()->delete();

        $depart = $feuille['etapes'][0][1];
        $dernier = count($feuille['etapes']) - 1;

        foreach ($feuille['etapes'] as $i => [$nomLieu, $heure]) {
            if (! isset($lieux[$nomLieu])) {
                $this->command?->warn("Lieu inconnu, étape ignorée : {$nomLieu}");

                continue;
            }

            EtapeParcours::create([
                'parcours_id' => $parcours->id,
                'lieu_id' => $lieux[$nomLieu]->id,
                'ordre' => $i + 1,
                'est_terminus' => $i === $dernier,
                'minutes_depuis_depart' => $this->minutesEntre($depart, $heure),
            ]);
        }
    }

    private function creerChauffeurs(array $feuille): void
    {
        foreach ($feuille['chauffeurs'] as $c) {
            $user = User::firstOrNew(['telephone_bus' => $c['tel']]);

            $user->fill([
                'first_name' => Str::title($c['nom']),
                'last_name' => 'INSAM',
                'role_bus' => RoleUtilisateur::Chauffeur->value,
                'espace' => 'bus',
                'actif_bus' => true,
            ]);

            // Le code n'est pose qu'a la creation : une relance du seeder
            // ne doit pas ramener a 0000 le code qu'un chauffeur a deja
            // choisi, ce qui rouvrirait son compte a tout le monde.
            if (! $user->exists) {
                $user->pin = self::PIN_PAR_DEFAUT;
            }

            $user->save();

            Chauffeur::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'matricule' => 'CH-'.substr($c['tel'], -4),
                    'disponible_secours' => true,
                ]
            );
        }
    }

    /** Ecart en minutes entre deux heures « HH:MM » de la meme matinee. */
    private function minutesEntre(string $debut, string $fin): int
    {
        [$hd, $md] = array_map('intval', explode(':', $debut));
        [$hf, $mf] = array_map('intval', explode(':', $fin));

        return max(($hf * 60 + $mf) - ($hd * 60 + $md), 0);
    }

    /** 690668217 => 690 66 82 17. */
    private function formatTelephone(string $tel): string
    {
        return trim(chunk_split(substr($tel, 0, 3), 3, ' ').chunk_split(substr($tel, 3), 2, ' '));
    }
}
