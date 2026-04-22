<?php
// src/Controller/ClientAnalysisController.php
namespace App\Controller;

use App\Repository\ClientRepository;
use App\Service\ClientAnalysisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/client/analyse')]
class ClientAnalysisController extends AbstractController
{
    #[Route('', name: 'app_client_analysis')]
    public function index(
        ClientRepository $clientRepository,
        ClientAnalysisService $analysisService
    ): Response
    {
        $clients = $clientRepository->findAll();
        $statsGlobales = $analysisService->getGlobalStats();
        
        // Analyser chaque client
        $clientsAnalyses = [];
        foreach ($clients as $client) {
            $clientsAnalyses[] = [
                'client' => $client,
                'analyse' => $analysisService->analyzeClient($client),
            ];
        }
        
        // Trier par score de fidélité (décroissant)
        usort($clientsAnalyses, function($a, $b) {
            return $b['analyse']['score_fidelite'] <=> $a['analyse']['score_fidelite'];
        });
        
        return $this->render('client/analysis/index.html.twig', [
            'clients_analyses' => $clientsAnalyses,
            'stats_globales' => $statsGlobales,
        ]);
    }
}
