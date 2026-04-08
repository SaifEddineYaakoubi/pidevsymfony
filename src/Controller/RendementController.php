<?php

namespace App\Controller;

use App\Entity\Rendement;
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
    public function index(RendementRepository $rendementRepository): Response
    {
        return $this->render('rendement/index.html.twig', [
            'rendements' => $rendementRepository->findAll(),
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
    public function show(Rendement $rendement): Response
    {
        return $this->render('rendement/show.html.twig', [
            'rendement' => $rendement,
        ]);
    }

    #[Route('/{idRendement}/edit', name: 'app_rendement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Rendement $rendement, EntityManagerInterface $entityManager): Response
    {
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
    public function delete(Request $request, Rendement $rendement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$rendement->getIdRendement(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($rendement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_rendement_index', [], Response::HTTP_SEE_OTHER);
    }
}
