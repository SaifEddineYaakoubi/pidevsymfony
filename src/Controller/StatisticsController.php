<?php
// src/Controller/StatisticsController.php
namespace App\Controller;

use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/statistiques')]
class StatisticsController extends AbstractController
{
    #[Route('', name: 'app_admin_statistics')]
    public function index(StatisticsService $statisticsService): Response
    {
        $ventesParMois = $statisticsService->getVentesParMois();
        $topProduits = $statisticsService->getTopProduits(5);
        $topClients = $statisticsService->getTopClients(5);
        $comparaison = $statisticsService->getComparaisonMensuelle();
        $statsGlobales = $statisticsService->getStatistiquesGlobales();
        $prediction = $statisticsService->getPredictionRevenusMoisProchain();
        $tauxCroissance = $statisticsService->getTauxCroissance();

        // Préparer les données pour les graphiques
        $moisLabels = [];
        $moisVentes = [];
        $moisRevenus = [];
        
        foreach ($ventesParMois as $data) {
            $moisLabels[] = date('M Y', (int) strtotime($data['mois'] . '-01'));
            $moisVentes[] = $data['nombre_ventes'];
            $moisRevenus[] = round($data['total_revenus'], 2);
        }

        return $this->render('admin/statistics/index.html.twig', [
            'stats_globales' => $statsGlobales,
            'mois_labels' => json_encode($moisLabels),
            'mois_ventes' => json_encode($moisVentes),
            'mois_revenus' => json_encode($moisRevenus),
            'top_produits' => $topProduits,
            'top_clients' => $topClients,
            'comparaison' => $comparaison,
            'prediction' => $prediction,
            'taux_croissance' => $tauxCroissance,
        ]);
    }
}
