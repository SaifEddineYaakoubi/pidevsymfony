<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\SoilAnalysisService;

#[Route('/test')]
class TestController extends AbstractController
{
    /**
     * Page de test pour vérifier que tout fonctionne
     * Accessible sur: http://localhost:8000/test/soil
     */
    #[Route('/soil', name: 'test_soil_analysis', methods: ['GET'])]
    public function testSoilAnalysis(SoilAnalysisService $soilService): Response
    {
        // Données mock pour test sans avoir de récolte réelle
        $mockData = [
            'npk' => [
                'nitrogen' => 45,
                'phosphorus' => 25,
                'potassium' => 200,
            ],
            'ph' => 6.5,
            'humidity' => 55,
            'soil_type' => 'Loamy',
            'soil_quality' => 78,
            'recommendations' => [
                'nitrogen' => [
                    'status' => 'OPTIMAL',
                    'level' => 45,
                    'recommended_action' => 'Maintenir les apports actuels',
                    'priority' => 'LOW'
                ],
                'phosphorus' => [
                    'status' => 'OPTIMAL',
                    'level' => 25,
                    'recommended_action' => 'Maintenir les apports actuels',
                    'priority' => 'LOW'
                ],
                'potassium' => [
                    'status' => 'OPTIMAL',
                    'level' => 200,
                    'recommended_action' => 'Maintenir les apports actuels',
                    'priority' => 'LOW'
                ],
            ],
            'harvest_compatibility' => [
                'soil_type_match' => 'GOOD',
                'pH_match' => 'OPTIMAL',
                'overall_suitability' => 'EXCELLENT'
            ]
        ];

        return $this->render('test/soil_analysis.html.twig', [
            'soil_analysis' => $mockData,
            'test_mode' => true,
        ]);
    }

    /**
     * Test API JSON
     * Accessible sur: http://localhost:8000/test/api/soil
     */
    #[Route('/api/soil', name: 'test_api_soil_analysis', methods: ['GET'])]
    public function testSoilAnalysisAPI(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'success',
            'test_mode' => true,
            'message' => '✓ L\'API AgroAPI fonctionne correctement',
            'data' => [
                'soil_quality_score' => 78,
                'productivity' => 3.5,
                'correlation' => 'POSITIVE - Rendement supérieur aux attentes du sol',
                'soil_factors_affecting_yield' => [],
                'recommendations_to_improve' => [],
                'predicted_yield_potential' => 4.2,
                'actual_vs_potential' => [
                    'actual' => 3.5,
                    'potential' => 4.2,
                    'efficiency' => '83.33%'
                ]
            ]
        ]);
    }

    /**
     * Test Connexion API Externe
     * Accessible sur: http://localhost:8000/test/api-connection
     */
    #[Route('/api-connection', name: 'test_api_connection', methods: ['GET'])]
    public function testAPIConnection(): JsonResponse
    {
        $checks = [
            'http_client' => class_exists('Symfony\\Contracts\\HttpClient\\HttpClientInterface') ? '✓' : '✗',
            'cache' => function_exists('apcu_fetch') ? '✓' : '✗',
            'soil_service' => class_exists('App\\Service\\SoilAnalysisService') ? '✓' : '✗',
            'soil_controller' => class_exists('App\\Controller\\SoilAnalysisController') ? '✓' : '✗',
        ];

        return new JsonResponse([
            'status' => 'testing',
            'checks' => $checks,
            'message' => 'Vérifiez que toutes les cases affichent ✓'
        ]);
    }

    /**
     * Test d'une Simulation Complète
     * Accessible sur: http://localhost:8000/test/simulation
     */
    #[Route('/simulation', name: 'test_simulation', methods: ['GET'])]
    public function testSimulation(): Response
    {
        // Simulation complet avec tous les scénarios
        $scenarios = [
            'excellent_yield' => [
                'soil_quality' => 90,
                'productivity' => 4.8,
                'efficiency' => '95%',
                'status' => 'EXCELLENT - Rendement exceptionnel',
                'color' => 'success'
            ],
            'good_yield' => [
                'soil_quality' => 75,
                'productivity' => 3.5,
                'efficiency' => '83%',
                'status' => 'GOOD - Performance optimale',
                'color' => 'info'
            ],
            'poor_yield' => [
                'soil_quality' => 50,
                'productivity' => 1.8,
                'efficiency' => '50%',
                'status' => 'POOR - À améliorer',
                'color' => 'warning'
            ],
            'critical_yield' => [
                'soil_quality' => 30,
                'productivity' => 0.8,
                'efficiency' => '20%',
                'status' => 'CRITICAL - Action urgente',
                'color' => 'danger'
            ],
        ];

        return $this->render('test/simulation.html.twig', [
            'scenarios' => $scenarios,
        ]);
    }

    /**
     * Test Recommandations Fertilisation
     * Accessible sur: http://localhost:8000/test/recommendations
     */
    #[Route('/recommendations', name: 'test_recommendations', methods: ['GET'])]
    public function testRecommendations(): Response
    {
        $recommendations = [
            [
                'nutrient' => 'Nitrogen',
                'status' => 'DEFICIENT',
                'level' => 15,
                'recommended_action' => 'Ajouter engrais azoté - apport 50-100 kg/ha',
                'priority' => 'HIGH',
                'expected_increase' => '15-20%'
            ],
            [
                'nutrient' => 'Phosphorus',
                'status' => 'OPTIMAL',
                'level' => 28,
                'recommended_action' => 'Maintenir les apports actuels',
                'priority' => 'LOW',
                'expected_increase' => '0%'
            ],
            [
                'nutrient' => 'Potassium',
                'status' => 'EXCESS',
                'level' => 350,
                'recommended_action' => 'Réduire apports potassiques - risque de pollution',
                'priority' => 'MEDIUM',
                'expected_increase' => '-5%'
            ],
        ];

        return $this->render('test/recommendations.html.twig', [
            'recommendations' => $recommendations,
        ]);
    }
}
