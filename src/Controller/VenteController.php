<?php

namespace App\Controller;

use App\Entity\Vente;
use App\Form\VenteType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/vente')]
final class VenteController extends AbstractController
{
    #[Route(name: 'app_vente_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer les paramètres de recherche et tri depuis l'URL
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sortBy', 'date_vente');
        $order = $request->query->get('order', 'DESC');

        // Récupérer le repository
        $venteRepository = $entityManager->getRepository(Vente::class);

        // Utiliser la méthode de recherche et tri
        $ventes = $venteRepository->findBySearchAndSort(
            !empty($search) ? $search : null,
            $sortBy,
            $order
        );

        // Déterminer l'ordre inverse pour les liens de tri
        $nextOrder = ($order === 'ASC') ? 'DESC' : 'ASC';

        return $this->render('vente/index.html.twig', [
            'ventes' => $ventes,
            'search' => $search,
            'sortBy' => $sortBy,
            'order' => $order,
            'nextOrder' => $nextOrder,
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

            $this->addFlash('success', 'La vente a été créée avec succès.');
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

            $this->addFlash('success', 'La vente a été modifiée avec succès.');
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

            $this->addFlash('success', 'La vente a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_vente_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/export/pdf', name: 'app_vente_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer les paramètres de filtrage
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sortBy', 'date_vente');
        $order = $request->query->get('order', 'DESC');

        // Récupérer le repository et les ventes
        $venteRepository = $entityManager->getRepository(Vente::class);
        $ventes = $venteRepository->findBySearchAndSort(
            !empty($search) ? $search : null,
            $sortBy,
            $order
        );

        // Générer le HTML via Twig
        $html = $this->renderView('vente/pdf.html.twig', [
            'ventes' => $ventes,
            'search' => $search,
            'exportDate' => new \DateTime(),
        ]);

        // Configurer Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultMediaType', 'print');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Retourner le PDF en téléchargement
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="ventes_'.date('Y-m-d_H-i-s').'.pdf"',
            ]
        );
    }
}
