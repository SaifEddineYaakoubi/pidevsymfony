<?php

namespace App\Controller;

use App\Entity\Rendement;
use App\Entity\Utilisateur;
use App\Form\RendementType;
use App\Repository\RendementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/rendement')]
class RendementController extends AbstractController
{
    #[Route('/', name: 'app_rendement_index', methods: ['GET'])]
    public function index(RendementRepository $rendementRepository, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $q = $request->query->getString('q', '');
        $sort = $request->query->getString('sort', 'prod_desc');

        $rendements = $rendementRepository->findForIndexForUser($user, $q, $sort);
        $stats = $rendementRepository->getIndexStatsForUser($user, $q);

        if ($request->isXmlHttpRequest()) {
            return $this->render('rendement/_results.html.twig', [
                'rendements' => $rendements,
                'stats' => $stats,
                'q' => $q,
                'sort' => $sort,
            ]);
        }

        return $this->render('rendement/index.html.twig', [
            'rendements' => $rendements,
            'stats' => $stats,
            'q' => $q,
            'sort' => $sort,
        ]);
    }

    #[Route('/new', name: 'app_rendement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $rendement = new Rendement();
        $form = $this->createForm(RendementType::class, $rendement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Calcul automatique de la productivité
            $surface = $rendement->getSurfaceExploitee();
            $quantite = $rendement->getQuantiteTotale();
            $productivite = $surface > 0 ? $quantite / $surface : 0;
            $rendement->setProductivite($productivite);

            $entityManager->persist($rendement);
            $entityManager->flush();

            return $this->redirectToRoute('app_rendement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('rendement/new.html.twig', [
            'rendement' => $rendement,
            'form' => $form,
        ]);
    }

    #[Route('/{idRendement}', name: 'app_rendement_show', methods: ['GET'])]
    public function show(int $idRendement, RendementRepository $rendementRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $rendement = $rendementRepository->findOneForUser($idRendement, $user);
        if (!$rendement) {
            throw $this->createNotFoundException('Rendement non trouvé');
        }

        return $this->render('rendement/show.html.twig', [
            'rendement' => $rendement,
        ]);
    }

    #[Route('/{idRendement}/edit', name: 'app_rendement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $idRendement, RendementRepository $rendementRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $rendement = $rendementRepository->findOneForUser($idRendement, $user);
        if (!$rendement) {
            throw $this->createNotFoundException('Rendement non trouvé');
        }

        $form = $this->createForm(RendementType::class, $rendement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Recalcul automatique de la productivité
            $surface = $rendement->getSurfaceExploitee();
            $quantite = $rendement->getQuantiteTotale();
            $productivite = $surface > 0 ? $quantite / $surface : 0;
            $rendement->setProductivite($productivite);

            $entityManager->flush();

            return $this->redirectToRoute('app_rendement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('rendement/edit.html.twig', [
            'rendement' => $rendement,
            'form' => $form,
        ]);
    }

    #[Route('/{idRendement}', name: 'app_rendement_delete', methods: ['POST'])]
    public function delete(Request $request, int $idRendement, RendementRepository $rendementRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $rendement = $rendementRepository->findOneForUser($idRendement, $user);
        if (!$rendement) {
            throw $this->createNotFoundException('Rendement non trouvé');
        }

        if ($this->isCsrfTokenValid('delete'.$rendement->getIdRendement(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($rendement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rendement_index', [], Response::HTTP_SEE_OTHER);
    }
}
