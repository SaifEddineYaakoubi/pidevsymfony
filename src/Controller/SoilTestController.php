<?php

namespace App\Controller;

use App\Entity\Recolte;
use App\Entity\Rendement;
use App\Entity\Utilisateur;
use App\Repository\RecolteRepository;
use App\Repository\RendementRepository;
use App\Service\SoilAnalysisService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/test-soil', name: 'test_soil_')]
class SoilTestController extends AbstractController
{
    /**
     * Page de test pour l'API AgroAPI
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        RecolteRepository $recolteRepository,
        RendementRepository $rendementRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        // Récupérer les récoltes de l'utilisateur
        $recoltes = $recolteRepository->findBy(['utilisateur' => $user], ['date_recolte' => 'DESC'], 10);

        // Récupérer seulement les rendements avec récoltes valides (INNER JOIN en base)
        $rendements = $rendementRepository->findAllWithValidRecoltes();

        return $this->render('test_soil/index.html.twig', [
            'recoltes' => $recoltes,
            'rendements' => $rendements,
        ]);
    }

    /**
     * Tester analyse récolte avec affichage du JSON
     */
    #[Route('/recolte/{id_recolte}/test', name: 'test_recolte', methods: ['GET'])]
    public function testRecolte(
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

        try {
            $soilAnalysis = $soilService->getSoilAnalysisForRecolte($recolte);
        } catch (\Exception $e) {
            $soilAnalysis = [
                'error' => true,
                'message' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s'),
            ];
        }

        return $this->render('test_soil/test_recolte.html.twig', [
            'recolte' => $recolte,
            'soil_analysis' => $soilAnalysis,
            'json_pretty' => json_encode($soilAnalysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Tester analyse rendement avec affichage du JSON
     */
    #[Route('/rendement/{idRendement}/test', name: 'test_rendement', methods: ['GET'])]
    public function testRendement(
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

        // Vérifier propriété
        $recolte = $rendement->getId_recolte();
        if (!$recolte || $recolte->getUtilisateur()?->getIdUser() !== $user->getIdUser()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $yieldAnalysis = $soilService->analyzeImpactOnYield($rendement);
        } catch (\Exception $e) {
            $yieldAnalysis = [
                'error' => true,
                'message' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s'),
            ];
        }

        return $this->render('test_soil/test_rendement.html.twig', [
            'rendement' => $rendement,
            'recolte' => $recolte,
            'yield_analysis' => $yieldAnalysis,
            'json_pretty' => json_encode($yieldAnalysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * Endpoint API pour tester directement
     */
    #[Route('/api/recolte/{id_recolte}', name: 'api_test_recolte', methods: ['GET'])]
    public function apiTestRecolte(
        int $id_recolte,
        RecolteRepository $recolteRepository,
        SoilAnalysisService $soilService
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $recolte = $recolteRepository->findOneForUser($id_recolte, $user);
        if (!$recolte) {
            return $this->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $soilAnalysis = $soilService->getSoilAnalysisForRecolte($recolte);
            return $this->json($soilAnalysis);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Endpoint API pour tester rendement
     */
    #[Route('/api/rendement/{idRendement}', name: 'api_test_rendement', methods: ['GET'])]
    public function apiTestRendement(
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
        if (!$recolte || $recolte->getUtilisateur()?->getIdUser() !== $user->getIdUser()) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $yieldAnalysis = $soilService->analyzeImpactOnYield($rendement);
            return $this->json($yieldAnalysis);
        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

