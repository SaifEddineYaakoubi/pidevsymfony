<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/ai-analytics')]
class AIAnalyticsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/', name: 'app_ai_analytics', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('admin/ai_analytics/index.html.twig');
    }

    #[Route('/data', name: 'app_ai_analytics_data', methods: ['GET'])]
    public function getData(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Récupérer tous les utilisateurs
        $users = $this->entityManager->getRepository(Utilisateur::class)->findAll();
        
        // Calculer les statistiques
        $stats = $this->calculateStatistics($users);
        
        return new JsonResponse($stats);
    }

    /**
     * @param Utilisateur[] $users
     * @return array<string, mixed>
     */
    private function calculateStatistics(array $users): array
    {
        $totalUsers = count($users);
        $ages = [];
        $sexes = [
            'homme' => 0,
            'femme' => 0,
            'autre' => 0,
            'non_specifie' => 0
        ];
        $ageRanges = [
            '0-17' => 0,
            '18-24' => 0,
            '25-34' => 0,
            '35-44' => 0,
            '45-54' => 0,
            '55-64' => 0,
            '65+' => 0
        ];

        foreach ($users as $user) {
            // Calcul de l'âge
            if ($user->getDateNaissance()) {
                $age = $user->getAge();
                if ($age !== null) {
                    $ages[] = $age;
                    
                    // Répartition par tranche d'âge
                    if ($age < 18) {
                        $ageRanges['0-17']++;
                    } elseif ($age < 25) {
                        $ageRanges['18-24']++;
                    } elseif ($age < 35) {
                        $ageRanges['25-34']++;
                    } elseif ($age < 45) {
                        $ageRanges['35-44']++;
                    } elseif ($age < 55) {
                        $ageRanges['45-54']++;
                    } elseif ($age < 65) {
                        $ageRanges['55-64']++;
                    } else {
                        $ageRanges['65+']++;
                    }
                }
            }

            // Répartition par sexe
            $sexe = strtolower($user->getSexe() ?? '');
            if ($sexe === 'homme' || $sexe === 'h' || $sexe === 'm' || $sexe === 'male') {
                $sexes['homme']++;
            } elseif ($sexe === 'femme' || $sexe === 'f' || $sexe === 'female') {
                $sexes['femme']++;
            } elseif ($sexe === 'autre' || $sexe === 'other') {
                $sexes['autre']++;
            } else {
                $sexes['non_specifie']++;
            }
        }

        // Calcul de la moyenne d'âge
        $averageAge = count($ages) > 0 ? round(array_sum($ages) / count($ages), 1) : 0;
        
        // Calcul de l'âge médian
        $medianAge = 0;
        if (count($ages) > 0) {
            sort($ages);
            $middle = floor(count($ages) / 2);
            if (count($ages) % 2 == 0) {
                $medianAge = ($ages[$middle - 1] + $ages[$middle]) / 2;
            } else {
                $medianAge = $ages[$middle];
            }
        }

        // Calcul des pourcentages
        $sexePercentages = [];
        foreach ($sexes as $key => $count) {
            $sexePercentages[$key] = $totalUsers > 0 ? round(($count / $totalUsers) * 100, 1) : 0;
        }

        $ageRangePercentages = [];
        foreach ($ageRanges as $key => $count) {
            $ageRangePercentages[$key] = $totalUsers > 0 ? round(($count / $totalUsers) * 100, 1) : 0;
        }

        // Prédictions IA (simulation simple)
        $predictions = $this->generatePredictions($ages, $sexes, $totalUsers);

        return [
            'total_users' => $totalUsers,
            'average_age' => $averageAge,
            'median_age' => round($medianAge, 1),
            'age_range' => [
                'min' => count($ages) > 0 ? min($ages) : 0,
                'max' => count($ages) > 0 ? max($ages) : 0
            ],
            'sexe_distribution' => [
                'counts' => $sexes,
                'percentages' => $sexePercentages
            ],
            'age_ranges' => [
                'counts' => $ageRanges,
                'percentages' => $ageRangePercentages
            ],
            'predictions' => $predictions,
            'insights' => $this->generateInsights($averageAge, $sexes, $ageRanges, $totalUsers)
        ];
    }

    /**
     * @param int[] $ages
     * @param array<string, int> $sexes
     * @return array<string, mixed>
     */
    private function generatePredictions(array $ages, array $sexes, int $totalUsers): array
    {
        $predictions = [];

        // Prédiction de croissance
        $predictions['growth_trend'] = $totalUsers > 10 ? 'positive' : 'stable';
        $predictions['growth_percentage'] = rand(5, 15);

        // Prédiction démographique
        if (count($ages) > 0) {
            $avgAge = array_sum($ages) / count($ages);
            if ($avgAge < 30) {
                $predictions['demographic_trend'] = 'Audience jeune et dynamique';
            } elseif ($avgAge < 45) {
                $predictions['demographic_trend'] = 'Audience mature et expérimentée';
            } else {
                $predictions['demographic_trend'] = 'Audience senior et établie';
            }
        } else {
            $predictions['demographic_trend'] = 'Données insuffisantes';
        }

        // Prédiction de genre dominant
        $maxSexe = array_keys($sexes, max($sexes))[0] ?? 'non_specifie';
        $predictions['dominant_gender'] = $maxSexe;

        return $predictions;
    }

    /**
     * @param array<string, int> $sexes
     * @param array<string, int> $ageRanges
     * @return array<int, array<string, string>>
     */
    private function generateInsights(float $averageAge, array $sexes, array $ageRanges, int $totalUsers): array
    {
        $insights = [];

        // Insight sur l'âge moyen
        if ($averageAge > 0) {
            if ($averageAge < 25) {
                $insights[] = [
                    'type' => 'info',
                    'icon' => 'fa-users',
                    'title' => 'Audience Jeune',
                    'message' => "Votre audience est principalement jeune (moyenne: {$averageAge} ans). Privilégiez les contenus modernes et dynamiques."
                ];
            } elseif ($averageAge < 40) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => 'fa-chart-line',
                    'title' => 'Audience Équilibrée',
                    'message' => "Votre audience a un âge moyen de {$averageAge} ans, idéal pour une plateforme professionnelle."
                ];
            } else {
                $insights[] = [
                    'type' => 'warning',
                    'icon' => 'fa-user-tie',
                    'title' => 'Audience Mature',
                    'message' => "Votre audience est mature (moyenne: {$averageAge} ans). Misez sur l'expertise et la fiabilité."
                ];
            }
        }

        // Insight sur la répartition par sexe
        $hommes = $sexes['homme'];
        $femmes = $sexes['femme'];
        $total = $hommes + $femmes;
        
        if ($total > 0) {
            $ratio = abs($hommes - $femmes) / $total * 100;
            if ($ratio < 20) {
                $insights[] = [
                    'type' => 'success',
                    'icon' => 'fa-balance-scale',
                    'title' => 'Parité Équilibrée',
                    'message' => "Excellente parité homme/femme ({$hommes}H / {$femmes}F). Votre plateforme est inclusive."
                ];
            } else {
                $dominant = $hommes > $femmes ? 'masculine' : 'féminine';
                $insights[] = [
                    'type' => 'info',
                    'icon' => 'fa-venus-mars',
                    'title' => 'Audience à Dominante ' . ucfirst($dominant),
                    'message' => "Votre audience est majoritairement {$dominant}. Considérez des actions pour diversifier."
                ];
            }
        }

        // Insight sur la tranche d'âge dominante
        $maxAgeRange = count($ageRanges) > 0 ? (array_keys($ageRanges, max($ageRanges))[0] ?? '18-24') : '18-24';
        $insights[] = [
            'type' => 'info',
            'icon' => 'fa-chart-pie',
            'title' => 'Tranche d\'âge Dominante',
            'message' => "La tranche d'âge {$maxAgeRange} ans est la plus représentée sur votre plateforme."
        ];

        return $insights;
    }
}
