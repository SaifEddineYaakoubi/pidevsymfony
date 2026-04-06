<?php

namespace App\Controller;

use App\Entity\Vente;
use App\Form\VenteType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vente')]
final class VenteController extends AbstractController
{
    #[Route(name: 'app_vente_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $ventes = $entityManager
            ->createQuery('SELECT v FROM App\Entity\Vente v LEFT JOIN v.id_client c')
            ->getResult();

        return $this->render('vente/index.html.twig', [
            'ventes' => $ventes,
        ]);
    }

    #[Route('/new', name: 'app_vente_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vente = new Vente();
        $form = $this->createForm(VenteType::class, $vente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($vente);
            $entityManager->flush();

            return $this->redirectToRoute('app_vente_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vente/new.html.twig', [
            'vente' => $vente,
            'form' => $form,
        ]);
    }

    #[Route('/{id_vente}', name: 'app_vente_show', methods: ['GET'])]
    public function show(Vente $vente, EntityManagerInterface $entityManager): Response
    {
        // Nettoyer le client supprimé avant de rendre le template
        try {
            $client = $vente->getId_client();
            if ($client !== null) {
                // Essayer d'accéder au nom pour vérifier l'existence
                $client->getNom();
            }
        } catch (\Exception $e) {
            // Le client a été supprimé, on le met à null
            $vente->setId_client(null);
        }

        return $this->render('vente/show.html.twig', [
            'vente' => $vente,
        ]);
    }

    #[Route('/{id_vente}/edit', name: 'app_vente_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        // Nettoyer le client supprimé avant de créer le formulaire
        if ($vente->getId_client() !== null) {
            try {
                // Essayer d'accéder au nom du client pour vérifier qu'il existe
                $vente->getId_client()->getNom();
            } catch (\Exception $e) {
                // Le client n'existe pas ou est supprimé, le mettre à null
                $vente->setId_client(null);
            }
        }

        $form = $this->createForm(VenteType::class, $vente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_vente_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('vente/edit.html.twig', [
            'vente' => $vente,
            'form' => $form,
        ]);
    }

    #[Route('/{id_vente}', name: 'app_vente_delete', methods: ['POST'])]
    public function delete(Request $request, Vente $vente, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$vente->getId_vente(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($vente);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_vente_index', [], Response::HTTP_SEE_OTHER);
    }
}
