<?php

namespace App\Controller\Agriculteur;

use App\Entity\Utilisateur;
use App\Service\Ai\CulturePredictionEngine;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/agriculteur/ia', name: 'agri_ia_')]
final class AiPredictionController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CulturePredictionEngine $engine): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $predictions = $engine->generateForUser($user);

        return $this->render('agriculteur/ia/index.html.twig', [
            'predictions' => $predictions,
        ]);
    }
}
