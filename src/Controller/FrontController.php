<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\ClientRepository;
use App\Repository\CultureRepository;
use App\Repository\ParcelleRepository;
use App\Repository\RecolteRepository;
use App\Repository\RendementRepository;
use App\Repository\VenteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class FrontController extends AbstractController
{
    #[Route('/agriculteur', name: 'app_agriculteur_home')]
    public function home(
        ParcelleRepository $parcelleRepository,
        CultureRepository $cultureRepository,
        VenteRepository $venteRepository,
        ClientRepository $clientRepository,
        RecolteRepository $recolteRepository,
        RendementRepository $rendementRepository,
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('agriculteur/pages/home.html.twig', [
            'counts' => [
                'parcelles' => $parcelleRepository->count(['id_user' => $user]),
                // Cultures are scoped via parcelle owner; repository doesn't expose countForUser, so approximate via filtered search.
                'cultures' => count($cultureRepository->searchByQueryForUser($user, null, null, null)),
                'ventes' => $venteRepository->count(['id_user' => $user]),
                'clients' => $clientRepository->count(['id_user' => $user->getIdUser()]),
                'recoltes' => $recolteRepository->count(['utilisateur' => $user]),
                // Rendement is scoped via recolte owner; repository doesn't expose countForUser, so approximate via filtered query.
                'rendements' => count($rendementRepository->findForIndexForUser($user, null, 'prod_desc')),
            ],
        ]);
    }

    #[Route('/about', name: 'app_agriculteur_about')]
    public function about(): Response
    {
        return $this->render('agriculteur/pages/about.html.twig');
    }

    #[Route('/services', name: 'app_agriculteur_services')]
    public function services(): Response
    {
        return $this->render('agriculteur/pages/services.html.twig');
    }

    #[Route('/testimonials', name: 'app_agriculteur_testimonials')]
    public function testimonials(): Response
    {
        return $this->render('agriculteur/pages/testimonials.html.twig');
    }

    #[Route('/blog', name: 'app_agriculteur_blog')]
    public function blog(): Response
    {
        return $this->render('agriculteur/pages/blog.html.twig');
    }

    #[Route('/blog/{slug}', name: 'app_agriculteur_blog_details')]
    public function blogDetails(string $slug): Response
    {
        // Static demo details page for now.
        return $this->render('agriculteur/pages/blog_details.html.twig', [
            'slug' => $slug,
        ]);
    }

    #[Route('/contact', name: 'app_agriculteur_contact')]
    public function contact(): Response
    {
        return $this->render('agriculteur/pages/contact.html.twig');
    }
}

