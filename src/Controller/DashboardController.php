<?php

namespace App\Controller;

use App\Service\PredictionService;
use App\Repository\VenteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        PredictionService $predictionService,
        VenteRepository $venteRepository
    ): Response
    {
        // Récupérer la prédiction IA
        $prediction = $predictionService->predictNextMonthSales();
        
        // Récupérer les statistiques générales
        $stats = $venteRepository->getVenteStats();
        
        // Récupérer les dernières ventes
        $recentSales = $venteRepository->findBy(
            [],
            ['date_vente' => 'DESC'],
            5
        );

        return $this->render('dashboard/index.html.twig', [
            'prediction' => $prediction,
            'stats' => $stats,
            'recent_sales' => $recentSales
        ]);
    }

    #[Route('/dashboard/prediction', name: 'app_dashboard_prediction')]
    public function prediction(PredictionService $predictionService): Response
    {
        $prediction = $predictionService->predictNextMonthSales();
        
        return $this->json($prediction);
    }

    #[Route('/dashboard/check-dependencies', name: 'app_dashboard_check_dependencies')]
    public function checkDependencies(PredictionService $predictionService): Response
    {
        $dependencies = $predictionService->checkPythonDependencies();
        
        return $this->json($dependencies);
    }
}
