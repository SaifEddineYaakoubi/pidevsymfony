<?php

namespace App\Service\Ai;

use App\Entity\Utilisateur;
use App\Repository\CultureRepository;
use App\Repository\ParcelleRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Moteur de prédiction IA basé sur les données historiques de l'exploitation.
 * Fournit des recommandations stratégiques sans API externe.
 */
final class CulturePredictionEngine
{
    /** Base de connaissances agronomiques : culture → mois optimaux de plantation */
    private const CULTURE_SAISONS = [
        'tomate'         => [3,4,5],
        'carotte'        => [2,3,9,10],
        'courgette'      => [4,5,6],
        'oignon'         => [10,11,12,1,2],
        'ail'            => [10,11,12],
        'salade'         => [9,10,2,3],
        'haricot'        => [4,5,6,9],
        'concombre'      => [4,5,6],
        'poivron'        => [3,4,5],
        'piment'         => [3,4,5],
        'aubergine'      => [3,4,5],
        'pomme de terre' => [2,3,10,11],
        'blé'            => [11,12,1],
        'maïs'           => [4,5,6],
        'pois'           => [10,11,2,3],
        'lentille'       => [11,12,1],
        'orge'           => [11,12,1],
        'tournesol'      => [4,5,6],
        'pastèque'       => [4,5,6],
        'melon'          => [4,5,6],
        'fraise'         => [9,10,11],
        'olivier'        => [10,11,12,1,2], // Plantation arbres
        'agrumes'        => [3,4,9,10],
    ];

    /** Durées de cycle (jours) : culture → [min, max] */
    private const CULTURE_DUREE = [
        'tomate'         => [70,  90],
        'carotte'        => [70,  80],
        'courgette'      => [50,  60],
        'oignon'         => [100, 130],
        'ail'            => [150, 180],
        'salade'         => [45,  60],
        'haricot'        => [55,  70],
        'concombre'      => [50,  65],
        'poivron'        => [70,  90],
        'piment'         => [70,  90],
        'aubergine'      => [65,  80],
        'pomme de terre' => [80,  110],
        'blé'            => [150, 180],
        'maïs'           => [90,  120],
        'pastèque'       => [80,  100],
        'melon'          => [75,  90],
        'olivier'        => [1000, 1000], // Perenne
        'agrumes'        => [1000, 1000],
    ];

    /** Rendement théorique moyen (tonnes par hectare) : culture → rendement */
    private const CULTURE_RENDEMENT_THEORIQUE = [
        'tomate'         => 60,
        'carotte'        => 40,
        'courgette'      => 30,
        'oignon'         => 45,
        'ail'            => 10,
        'salade'         => 25,
        'haricot'        => 15,
        'concombre'      => 40,
        'poivron'        => 35,
        'piment'         => 20,
        'aubergine'      => 40,
        'pomme de terre' => 35,
        'blé'            => 5,
        'maïs'           => 9,
        'pastèque'       => 50,
        'melon'          => 25,
        'olivier'        => 4,
        'agrumes'        => 25,
    ];

    /** Rotations bénéfiques : après cette culture, recommander */
    private const ROTATIONS = [
        'tomate'         => ['haricot', 'salade', 'carotte'],
        'blé'            => ['pois', 'lentille', 'tomate'],
        'maïs'           => ['pois', 'haricot', 'blé'],
        'pomme de terre' => ['blé', 'maïs', 'oignon'],
        'oignon'         => ['carotte', 'salade', 'haricot'],
        'carotte'        => ['tomate', 'oignon', 'salade'],
        'haricot'        => ['blé', 'maïs', 'tomate'],
        'salade'         => ['tomate', 'poivron', 'concombre'],
        'pois'           => ['blé', 'maïs', 'tomate'],
        'concombre'      => ['salade', 'carotte', 'oignon'],
        'courgette'      => ['haricot', 'carotte', 'blé'],
    ];

    public function __construct(
        private readonly CultureRepository   $cultureRepo,
        private readonly ParcelleRepository  $parcelleRepo,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Point d'entrée principal : génère toutes les prédictions pour un utilisateur.
     */
    public function generateForUser(Utilisateur $user): array
    {
        $cultures   = $this->cultureRepo->searchByQueryForUser($user, null, null, null);
        $parcelles  = $this->parcelleRepo->findBy(['id_user' => $user]);

        // ── Prédictions par culture active ───────────────────────────
        $culturePredictions = [];
        foreach ($cultures as $culture) {
            $culturePredictions[] = $this->predictForCulture($culture);
        }

        // ── Recommandations par parcelle ─────────────────────────────
        $parcelleRecs = [];
        foreach ($parcelles as $parcelle) {
            // Cherche la culture actuelle sur cette parcelle
            $currentCulture = null;
            foreach ($cultures as $c) {
                if ($c->getParcelle() && $c->getParcelle()->getId_parcelle() === $parcelle->getId_parcelle()) {
                    $currentCulture = $c;
                    break;
                }
            }
            $parcelleRecs[] = $this->recommendForParcelle($parcelle, $currentCulture);
        }

        // ── Score global de l'exploitation (Basé sur l'état des cultures actives)
        $globalScore = $this->computeGlobalScore($cultures);

        // ── Meilleur moment pour planter (saison actuelle) ──────────
        $seasonRecs = $this->getSeasonalRecommendations();

        return [
            'global_score'        => $globalScore,
            'culture_predictions' => $culturePredictions,
            'parcelle_recs'       => $parcelleRecs,
            'seasonal_recs'       => $seasonRecs,
            'has_data'            => count($cultures) > 0 || count($parcelles) > 0,
            'cultures_count'      => count($cultures),
            'parcelles_count'     => count($parcelles),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  PRIVATE helpers
    // ─────────────────────────────────────────────────────────────

    /** Prédiction théorique pour une culture active */
    private function predictForCulture(object $culture): array
    {
        $type   = mb_strtolower((string) $culture->getTypeCulture());
        $etat   = $culture->getEtatCroissance();
        $today  = new \DateTime();

        // Jours avant récolte
        $jours = null;
        $urgence = 'normal';
        if ($culture->getDateRecoltePrevue()) {
            $diff = $today->diff($culture->getDateRecoltePrevue());
            $jours = $diff->invert ? -$diff->days : $diff->days;
            if ($jours <= 0)        $urgence = 'depasse';
            elseif ($jours <= 7)    $urgence = 'urgent';
            elseif ($jours <= 14)   $urgence = 'proche';
        }

        // Rendement estimé (théorique)
        $rendementEstime = null;
        $superficie = $culture->getParcelle() ? (float) $culture->getParcelle()->getSuperficie() : 0;
        
        if (isset(self::CULTURE_RENDEMENT_THEORIQUE[$type]) && $superficie > 0) {
            // Rendement théorique = Rendement par hectare * Superficie
            // On applique un facteur selon l'état d'avancement pour être plus réaliste
            $facteurAvancement = match($etat) {
                'maturite' => 1.0,
                'floraison' => 0.8,
                'croissance' => 0.5,
                default => 0.2,
            };
            
            $baseTonneHa = self::CULTURE_RENDEMENT_THEORIQUE[$type];
            $estimationTonnes = $baseTonneHa * $superficie * $facteurAvancement;
            $rendementEstime = round($estimationTonnes, 1);
            $conseil = "Basé sur les normes agronomiques, vous devriez récolter environ {$rendementEstime} tonnes.";
        } else {
            $conseil = "Pratiquez de bons soins pour maximiser votre récolte.";
        }

        // Score risque 0-100
        $risqueScore = $this->computeRisque($etat, $jours);
        $risqueLabel = match(true) {
            $risqueScore >= 75 => 'élevé',
            $risqueScore >= 40 => 'modéré',
            default            => 'faible',
        };

        // Conseil d'état
        $etatConseil = match($etat) {
            'germination' => "Assurez une humidité constante et évitez les excès d'eau.",
            'croissance'  => "Période critique : apportez de l'azote et surveillez les parasites.",
            'floraison'   => "Évitez les pesticides pour préserver les pollinisateurs.",
            'maturite'    => "Préparez la récolte. Réduisez l'irrigation progressive.",
            default       => "Surveillez régulièrement l'état de la culture.",
        };

        return [
            'culture'          => $culture,
            'type'             => $culture->getTypeCulture(),
            'etat'             => $etat,
            'jours_recolte'    => $jours,
            'urgence'          => $urgence,
            'rendement_estime' => $rendementEstime,
            'risque_score'     => $risqueScore,
            'risque_label'     => $risqueLabel,
            'conseil'          => $conseil,
            'etat_conseil'     => $etatConseil,
        ];
    }

    /** Score de risque basé sur état + jours */
    private function computeRisque(string $etat, ?int $jours): int
    {
        $base = match($etat) {
            'maturite'   => 50,
            'floraison'  => 30,
            'croissance' => 20,
            default      => 10,
        };

        if ($jours === null) return $base;
        if ($jours <= 0)  return min(100, $base + 50);
        if ($jours <= 7)  return min(100, $base + 30);
        if ($jours <= 14) return min(100, $base + 15);

        return $base;
    }

    /** Recommandation de rotation pour une parcelle */
    private function recommendForParcelle(object $parcelle, ?object $currentCulture): array
    {
        $last = $currentCulture ? mb_strtolower((string) $currentCulture->getTypeCulture()) : null;
        $suggestions = [];
        
        if ($last && isset(self::ROTATIONS[$last])) {
            $suggestions = self::ROTATIONS[$last];
        } else {
            $suggestions = ['tomate', 'haricot', 'salade'];
        }

        if ($last) {
            $conseil = "Après votre culture de \"$last\", une rotation vers " . implode(', ', $suggestions) . " est recommandée pour préserver la fertilité du sol.";
        } else {
            $conseil = "Parcelle libre. Commencez par des cultures peu exigeantes comme la salade ou le haricot, ou selon la saison.";
        }

        return [
            'parcelle'      => $parcelle,
            'last_culture'  => $last,
            'suggestions'   => $suggestions,
            'conseil'       => $conseil,
        ];
    }

    /** Score de santé globale (0-100) basé sur l'état des cultures */
    private function computeGlobalScore(array $cultures): array
    {
        if (empty($cultures)) {
            return ['score' => 0, 'label' => 'En attente', 'color' => '#6c757d', 'message' => "Aucune culture active. Lancez votre première plantation !"];
        }

        $totalScore = 0;
        foreach ($cultures as $c) {
            // Un état avancé = bon signe
            $scoreEtat = match($c->getEtatCroissance()) {
                'maturite' => 100,
                'floraison' => 75,
                'croissance' => 50,
                'germination' => 25,
                default => 0,
            };
            $totalScore += $scoreEtat;
        }

        $score = (int) round($totalScore / count($cultures));

        [$label, $color, $message] = match(true) {
            $score >= 80 => ['Excellent', '#10b981', "Vos cultures sont en phase de maturité. Préparez-vous aux récoltes !"],
            $score >= 60 => ['Bon',       '#3b82f6', "Croissance saine. Vos cultures se développent bien."],
            $score >= 40 => ['Moyen',     '#f59e0b', "Phase de développement intermédiaire. Continuez le suivi."],
            $score >= 20 => ['Démarrage', '#8b5cf6', "Cultures en phase initiale (germination). Attention à l'arrosage."],
            default      => ['Critique',  '#ef4444', "Besoin d'attention urgente sur vos cultures."],
        };

        return compact('score', 'label', 'color', 'message');
    }

    /** Recommandations saisonnières basées sur le mois actuel */
    private function getSeasonalRecommendations(): array
    {
        $moisActuel = (int) date('n');
        $recs = [];
        foreach (self::CULTURE_SAISONS as $culture => $moisOptimaux) {
            if (in_array($moisActuel, $moisOptimaux, true)) {
                $duree = self::CULTURE_DUREE[$culture] ?? [60, 90];
                $recs[] = [
                    'culture' => ucfirst($culture),
                    'duree'   => $duree,
                    'emoji'   => $this->emoji($culture),
                ];
            }
        }
        return $recs;
    }



    private function emoji(string $culture): string
    {
        return match(true) {
            str_contains($culture, 'tomate')  => '🍅',
            str_contains($culture, 'carotte') => '🥕',
            str_contains($culture, 'oignon')  => '🧅',
            str_contains($culture, 'ail')     => '🧄',
            str_contains($culture, 'salade')  => '🥗',
            str_contains($culture, 'haricot') => '🫛',
            str_contains($culture, 'poivron') => '🫑',
            str_contains($culture, 'piment')  => '🌶️',
            str_contains($culture, 'aubergine')=> '🍆',
            str_contains($culture, 'blé')     => '🌾',
            str_contains($culture, 'orge')    => '🌾',
            str_contains($culture, 'maïs')    => '🌽',
            str_contains($culture, 'pastèque')=> '🍉',
            str_contains($culture, 'melon')   => '🍈',
            str_contains($culture, 'fraise')  => '🍓',
            str_contains($culture, 'pomme')   => '🥔',
            str_contains($culture, 'olivier') => '🫒',
            str_contains($culture, 'agrumes') => '🍋',
            default                           => '🌱',
        };
    }
}
