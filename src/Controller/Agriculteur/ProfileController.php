<?php

namespace App\Controller\Agriculteur;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ProfileController extends AbstractController
{
    #[Route('/agriculteur/profil', name: 'app_agriculteur_profile')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_AGRICULTEUR');

        return $this->render('agriculteur/profile/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}

