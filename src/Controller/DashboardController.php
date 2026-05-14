<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Service\PredictionService;
use App\Repository\VenteRepository;
use App\Repository\StockRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        PredictionService $predictionService,
        VenteRepository $venteRepository,
        StockRepository $stockRepository,
        ProduitRepository $produitRepository,
    ): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $isResponsableStock = $user instanceof Utilisateur && $user->getRole() === 'responsable_stock';

        // Récupérer la prédiction IA
        $prediction = $predictionService->predictNextMonthSales();

        if ($isResponsableStock) {
            // Responsable stock : stats et données filtrées par utilisateur
            $stats = $venteRepository->getVenteStatsForUser($user);
            $recentSales = $venteRepository->findBy(
                ['id_user' => $user],
                ['date_vente' => 'DESC'],
                5
            );
            $stockCount  = $stockRepository->count(['id_user' => $user]);
            $produitCount = count($produitRepository->findBySearchAndSortForUser($user, null, null));
        } else {
            // Admin : toutes les données
            $stats = $venteRepository->getVenteStats();
            $recentSales = $venteRepository->findBy(
                [],
                ['date_vente' => 'DESC'],
                5
            );
            $stockCount  = null;
            $produitCount = null;
        }

        return $this->render('dashboard/index.html.twig', [
            'prediction'    => $prediction,
            'stats'         => $stats,
            'recent_sales'  => $recentSales,
            'stock_count'   => $stockCount,
            'produit_count' => $produitCount,
            'is_responsable_stock' => $isResponsableStock,
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
