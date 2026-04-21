<?php

namespace App\Controller;

use App\Entity\Recolte;
use App\Entity\Rendement;
use App\Service\SoilAnalysisService;
use App\Repository\RecolteRepository;
use App\Repository\RendementRepository;
use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/soil')]
class SoilAnalysisController extends AbstractController
{
    /**
     * Afficher analyse du sol pour une récolte
     */
    #[Route('/recolte/{id_recolte}', name: 'app_soil_recolte', methods: ['GET'])]
    public function analyzeRecolte(
        int $id_recolte,
        RecolteRepository $recolteRepository,
        SoilAnalysisService $soilService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $recolte = $recolteRepository->findOneForUser($id_recolte, $user);
        if (!$recolte) {
            throw $this->createNotFoundException('Récolte non trouvée');
        }

        $soilAnalysis = $soilService->getSoilAnalysisForRecolte($recolte);

        return $this->render('soil_analysis/recolte.html.twig', [
            'recolte' => $recolte,
            'soil_analysis' => $soilAnalysis,
        ]);
    }

    /**
     * Afficher impact du sol sur rendement
     */
    #[Route('/rendement/{idRendement}', name: 'app_soil_rendement', methods: ['GET'])]
    public function analyzeRendement(
        int $idRendement,
        RendementRepository $rendementRepository,
        SoilAnalysisService $soilService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $rendement = $rendementRepository->find($idRendement);
        if (!$rendement) {
            throw $this->createNotFoundException('Rendement non trouvé');
        }

        // Vérifier que le rendement appartient à l'utilisateur via la récolte
        $recolte = $rendement->getId_recolte();
        if (!$recolte || $recolte->getId_user() !== $user->getIdUser()) {
            throw $this->createAccessDeniedException();
        }

        $yieldAnalysis = $soilService->analyzeImpactOnYield($rendement);

        return $this->render('soil_analysis/rendement.html.twig', [
            'rendement' => $rendement,
            'recolte' => $recolte,
            'yield_analysis' => $yieldAnalysis,
        ]);
    }

    /**
     * API endpoint: Analyse complète sol/rendement
     */
    #[Route('/api/rendement/{idRendement}/analysis', name: 'api_soil_rendement_analysis', methods: ['GET'])]
    public function getRendementAnalysisAPI(
        int $idRendement,
        RendementRepository $rendementRepository,
        SoilAnalysisService $soilService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $rendement = $rendementRepository->find($idRendement);
        if (!$rendement) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        $recolte = $rendement->getId_recolte();
        if (!$recolte || $recolte->getId_user() !== $user->getIdUser()) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $analysis = $soilService->analyzeImpactOnYield($rendement);

        return $this->json($analysis);
    }
}

