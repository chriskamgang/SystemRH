<?php

namespace App\Services;

use App\Enums\StatutMissionSecours;
use App\Enums\StatutPrime;
use App\Enums\StatutTournee;
use App\Models\BaremePrime;
use App\Models\Chauffeur;
use App\Models\CloturePaie;
use App\Models\MissionSecours;
use App\Models\Prime;
use App\Models\Tournee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Systeme de primes chauffeurs (section 3.3).
 *
 * Formule de reference du cahier des charges :
 *   Bonus Total = (Tours_Valides x P_tour) + (Secours x P_secours) + Prime_Assiduite - Penalites
 */
class PrimeService
{
    public const PRIME_TOUR = 'prime_tour';
    public const PRIME_SECOURS = 'prime_secours';
    public const PRIME_ASSIDUITE = 'prime_assiduite';
    public const BONUS_REGULARITE = 'bonus_regularite';
    public const PENALITE = 'penalite';

    /** Credite la prime liee a un tour valide (3.3). */
    public function crediterPrimeTour(Tournee $tournee): ?Prime
    {
        if ($tournee->statut !== StatutTournee::Termine) {
            return null;
        }

        // Idempotence : un tour ne genere qu'une seule prime, meme si l'evenement est rejoue.
        $existante = Prime::where('tournee_id', $tournee->id)
            ->where('type', self::PRIME_TOUR)
            ->first();

        if ($existante) {
            return $existante;
        }

        $bareme = $this->bareme(self::PRIME_TOUR);

        if (! $bareme) {
            return null;
        }

        $date = ($tournee->termine_le ?? now())->toImmutable();

        return Prime::create([
            'chauffeur_id' => $tournee->affectation->chauffeur_id,
            'bareme_id' => $bareme->id,
            'tournee_id' => $tournee->id,
            'type' => self::PRIME_TOUR,
            'libelle' => "Tour {$tournee->numero_tour} — {$tournee->affectation->ligne->nom}",
            'montant_fcfa' => $bareme->montant_fcfa,
            'date_acquisition' => $date->toDateString(),
            'periode' => $date->format('Y-m'),
            'statut' => StatutPrime::EnAttente,
        ]);
    }

    /**
     * Credite la prime de secours, sous reserve du controle croise (regle 3.4) :
     * panne confirmee ET prise en charge des passagers attestee.
     */
    public function crediterPrimeSecours(MissionSecours $mission): ?Prime
    {
        if (! $mission->ouvreDroitAPrime()) {
            return null;
        }

        $existante = Prime::where('mission_secours_id', $mission->id)
            ->where('type', self::PRIME_SECOURS)
            ->first();

        if ($existante) {
            return $existante;
        }

        $bareme = $this->bareme(self::PRIME_SECOURS);

        if (! $bareme) {
            return null;
        }

        $date = ($mission->terminee_le ?? now())->toImmutable();

        return Prime::create([
            'chauffeur_id' => $mission->chauffeur_id,
            'bareme_id' => $bareme->id,
            'mission_secours_id' => $mission->id,
            'type' => self::PRIME_SECOURS,
            'libelle' => "Mission de secours — bus {$mission->panne->bus->immatriculation}",
            'montant_fcfa' => $bareme->montant_fcfa,
            'date_acquisition' => $date->toDateString(),
            'periode' => $date->format('Y-m'),
            'statut' => StatutPrime::EnAttente,
        ]);
    }

    /**
     * Prime d'assiduite journaliere : due si le chauffeur a realise 100 % de ses tours prevus (3.3).
     * Idempotente, elle peut donc etre rejouee sans dupliquer le credit.
     */
    public function evaluerAssiduiteJournaliere(Chauffeur $chauffeur, CarbonImmutable $jour): ?Prime
    {
        $affectation = $chauffeur->affectations()
            ->whereDate('date_service', $jour->toDateString())
            ->first();

        if (! $affectation) {
            return null;
        }

        $toursValides = $affectation->tournees()->where('statut', StatutTournee::Termine)->count();

        if ($toursValides < $affectation->tours_prevus) {
            return null;
        }

        $existante = Prime::where('chauffeur_id', $chauffeur->id)
            ->where('type', self::PRIME_ASSIDUITE)
            ->whereDate('date_acquisition', $jour->toDateString())
            ->first();

        if ($existante) {
            return $existante;
        }

        $bareme = $this->bareme(self::PRIME_ASSIDUITE);

        if (! $bareme) {
            return null;
        }

        return Prime::create([
            'chauffeur_id' => $chauffeur->id,
            'bareme_id' => $bareme->id,
            'type' => self::PRIME_ASSIDUITE,
            'libelle' => "Assiduité du {$jour->format('d/m/Y')} — {$toursValides}/{$affectation->tours_prevus} tours",
            'montant_fcfa' => $bareme->montant_fcfa,
            'date_acquisition' => $jour->toDateString(),
            'periode' => $jour->format('Y-m'),
            'statut' => StatutPrime::EnAttente,
        ]);
    }

    /**
     * Bonus mensuel de regularite : aucun tour manque injustifie et ponctualite exemplaire (3.3).
     */
    public function evaluerBonusRegularite(Chauffeur $chauffeur, string $periode): ?Prime
    {
        [$annee, $mois] = explode('-', $periode);

        $affectations = $chauffeur->affectations()
            ->whereYear('date_service', $annee)
            ->whereMonth('date_service', $mois)
            ->where('statut', '!=', 'annulee')
            ->withCount([
                'tournees as tours_termines' => fn ($q) => $q->where('statut', StatutTournee::Termine),
                'tournees as tours_en_anomalie' => fn ($q) => $q->where('anomalie_duree', true),
            ])
            ->get();

        if ($affectations->isEmpty()) {
            return null;
        }

        $toutRealise = $affectations->every(
            fn ($a) => $a->tours_termines >= $a->tours_prevus && $a->tours_en_anomalie === 0,
        );

        if (! $toutRealise) {
            return null;
        }

        $existante = Prime::where('chauffeur_id', $chauffeur->id)
            ->where('type', self::BONUS_REGULARITE)
            ->where('periode', $periode)
            ->first();

        if ($existante) {
            return $existante;
        }

        $bareme = $this->bareme(self::BONUS_REGULARITE);

        if (! $bareme) {
            return null;
        }

        return Prime::create([
            'chauffeur_id' => $chauffeur->id,
            'bareme_id' => $bareme->id,
            'type' => self::BONUS_REGULARITE,
            'libelle' => "Bonus de régularité — {$periode}",
            'montant_fcfa' => $bareme->montant_fcfa,
            'date_acquisition' => CarbonImmutable::parse("{$periode}-01")->endOfMonth()->toDateString(),
            'periode' => $periode,
            'statut' => StatutPrime::EnAttente,
        ]);
    }

    /**
     * Regularise un tour signale en anomalie de duree (3.4) : apres verification,
     * le gestionnaire leve l'anomalie et le tour redevient eligible a la prime.
     */
    public function regulariserTour(Tournee $tournee, User $operateur, ?string $motif = null): ?Prime
    {
        if (! $tournee->anomalie_duree) {
            return null;
        }

        $tournee->update([
            'anomalie_duree' => false,
            'note_anomalie' => trim(($tournee->note_anomalie ?? '')
                ." — Régularisé par {$operateur->nom_complet} le "
                .now()->format('d/m/Y H:i')
                .($motif ? " : {$motif}" : '')),
        ]);

        return $this->crediterPrimeTour($tournee->refresh());
    }

    /** Applique une penalite : montant stocke en negatif pour rester sommable. */
    public function appliquerPenalite(Chauffeur $chauffeur, string $motif, int $montant, ?CarbonImmutable $date = null): Prime
    {
        $date ??= CarbonImmutable::now();

        return Prime::create([
            'chauffeur_id' => $chauffeur->id,
            'type' => self::PENALITE,
            'libelle' => $motif,
            'montant_fcfa' => -abs($montant),
            'date_acquisition' => $date->toDateString(),
            'periode' => $date->format('Y-m'),
            'statut' => StatutPrime::EnAttente,
        ]);
    }

    /**
     * Recapitulatif de la cagnotte d'un chauffeur sur une periode,
     * tel qu'affiche dans son tableau de bord in-app (3.3).
     */
    public function recapitulatif(Chauffeur $chauffeur, string $periode): array
    {
        $primes = $chauffeur->primes()
            ->where('periode', $periode)
            ->where('statut', '!=', StatutPrime::Annulee)
            ->get();

        $gains = $primes->where('montant_fcfa', '>', 0);
        $penalites = $primes->where('montant_fcfa', '<', 0);

        return [
            'periode' => $periode,
            'tours_valides' => $gains->where('type', self::PRIME_TOUR)->count(),
            'secours_realises' => $gains->where('type', self::PRIME_SECOURS)->count(),
            'jours_assiduite' => $gains->where('type', self::PRIME_ASSIDUITE)->count(),
            'total_primes_fcfa' => (int) $gains->sum('montant_fcfa'),
            'total_penalites_fcfa' => (int) abs($penalites->sum('montant_fcfa')),
            'net_a_payer_fcfa' => (int) $primes->sum('montant_fcfa'),
            'detail' => $primes->sortByDesc('date_acquisition')->values(),
        ];
    }

    /**
     * Cloture de paie mensuelle (3.5) : fige le recapitulatif de chaque chauffeur
     * et bascule les primes de la periode en "payee".
     */
    public function cloturerPeriode(string $periode, ?User $operateur = null): array
    {
        return DB::transaction(function () use ($periode, $operateur) {
            $clotures = [];

            foreach (Chauffeur::with('user')->get() as $chauffeur) {
                $recap = $this->recapitulatif($chauffeur, $periode);

                if ($recap['detail']->isEmpty()) {
                    continue;
                }

                $effectif = (int) $chauffeur->affectations()
                    ->whereHas('tournees')
                    ->with('tournees')
                    ->get()
                    ->flatMap->tournees
                    ->where('statut', StatutTournee::Termine)
                    ->filter(fn ($t) => $t->termine_le?->format('Y-m') === $periode)
                    ->sum('effectif_embarque');

                $cloture = CloturePaie::updateOrCreate(
                    ['periode' => $periode, 'chauffeur_id' => $chauffeur->id],
                    [
                        'tours_valides' => $recap['tours_valides'],
                        'secours_realises' => $recap['secours_realises'],
                        'jours_assiduite' => $recap['jours_assiduite'],
                        'effectif_transporte' => $effectif,
                        'total_primes_fcfa' => $recap['total_primes_fcfa'],
                        'total_penalites_fcfa' => $recap['total_penalites_fcfa'],
                        'net_a_payer_fcfa' => $recap['net_a_payer_fcfa'],
                        'statut' => 'cloturee',
                        'cloturee_par' => $operateur?->id,
                        'cloturee_le' => now(),
                    ],
                );

                $chauffeur->primes()
                    ->where('periode', $periode)
                    ->whereIn('statut', [StatutPrime::EnAttente, StatutPrime::Validee])
                    ->update([
                        'statut' => StatutPrime::Payee,
                        'validee_par' => $operateur?->id,
                        'validee_le' => now(),
                    ]);

                $clotures[] = $cloture;
            }

            return $clotures;
        });
    }

    private function bareme(string $code): ?BaremePrime
    {
        return BaremePrime::where('code', $code)->where('actif', true)->first();
    }
}
