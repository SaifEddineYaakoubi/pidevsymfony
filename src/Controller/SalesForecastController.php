<?php
// src/Controller/SalesForecastController.php
namespace App\Controller;

use App\Service\SalesForecastService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/vente/previsions')]
class SalesForecastController extends AbstractController
{
    #[Route('', name: 'app_vente_forecast')]
    public function index(SalesForecastService $forecastService): Response
    {
        $forecast = $forecastService->getForecast();
        $profitability = $forecastService->getProfitabilityAnalysis();
        $seasonality = $forecastService->getSeasonalityAnalysis();
        $recommendations = $forecastService->getStrategicRecommendations();
        
        // Préparer les données pour les graphiques
        $historiqueLabels = [];
        $historiqueVentes = [];
        $historiqueRevenus = [];
        
        // Vérifier que l'historique existe
        if (isset($forecast['historique']) && is_array($forecast['historique'])) {
            foreach ($forecast['historique'] as $data) {
                $historiqueLabels[] = date('M Y', strtotime($data['mois'] . '-01'));
                $historiqueVentes[] = $data['nombre_ventes'];
                $historiqueRevenus[] = round($data['revenus'], 2);
            }
            
            // Ajouter les prévisions
            if (isset($forecast['previsions']) && is_array($forecast['previsions'])) {
                foreach ($forecast['previsions'] as $prev) {
                    $historiqueLabels[] = $prev['mois'];
                    $historiqueVentes[] = $prev['ventes_prevues'];
                    $historiqueRevenus[] = $prev['revenus_prevus'];
                }
            }
        }
        
        return $this->render('vente/forecast/index.html.twig', [
            'forecast' => $forecast,
            'profitability' => $profitability,
            'seasonality' => $seasonality,
            'recommendations' => $recommendations,
            'historique_labels' => json_encode($historiqueLabels),
            'historique_ventes' => json_encode($historiqueVentes),
            'historique_revenus' => json_encode($historiqueRevenus),
            'nb_historique' => isset($forecast['historique']) ? count($forecast['historique']) : 0,
        ]);
    }
}
