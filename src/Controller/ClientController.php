<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Utilisateur;
use App\Form\ClientType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/client')]
final class ClientController extends AbstractController
{
    #[Route(name: 'app_client_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        // Récupérer les paramètres de recherche et tri depuis l'URL
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sortBy', 'nom');
        $order = $request->query->get('order', 'ASC');

        // Récupérer le repository
        $clientRepository = $entityManager->getRepository(Client::class);

        // Utiliser la méthode de recherche et tri
        $clients = $clientRepository->findBySearchAndSortForUser(
            $user,
            !empty($search) ? $search : null,
            $sortBy,
            $order
        );

        // Stats globales pour l'entête dashboard
        $stats = $clientRepository->getClientStatsForUser($user);

        // Déterminer l'ordre inverse pour les liens de tri
        $nextOrder = ($order === 'ASC') ? 'DESC' : 'ASC';

        return $this->render('client/index.html.twig', [
            'clients' => $clients,
            'stats' => $stats,
            'search' => $search,
            'sortBy' => $sortBy,
            'order' => $order,
            'nextOrder' => $nextOrder,
        ]);
    }

    #[Route('/new', name: 'app_client_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $client = new Client();
        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ensure id_user is filled (DB constraint: NOT NULL)
            $client->setId_user($user->getIdUser());

            $entityManager->persist($client);
            $entityManager->flush();

            $this->addFlash('success', 'Le client a été créé avec succès.');
            return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/new.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id_client}', name: 'app_client_show', methods: ['GET'])]
    public function show(int $id_client, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $clientRepo = $entityManager->getRepository(Client::class);
        $client = $clientRepo->findOneForUser($id_client, $user);
        if (!$client) {
            throw $this->createNotFoundException();
        }

        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id_client}/edit', name: 'app_client_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id_client, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $clientRepo = $entityManager->getRepository(Client::class);
        $client = $clientRepo->findOneForUser($id_client, $user);
        if (!$client) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ClientType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Ensure ownership stays correct
            if ($client->getId_user() === null) {
                $client->setId_user($user->getIdUser());
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le client a été modifié avec succès.');
            return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id_client}', name: 'app_client_delete', methods: ['POST'])]
    public function delete(Request $request, int $id_client, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $clientRepo = $entityManager->getRepository(Client::class);
        $client = $clientRepo->findOneForUser($id_client, $user);
        if (!$client) {
            throw $this->createNotFoundException();
        }

        if ($this->isCsrfTokenValid('delete'.$client->getId_client(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($client);
            $entityManager->flush();

            $this->addFlash('success', 'Le client a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/export/pdf', name: 'app_client_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        // Récupérer les paramètres de filtrage
        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sortBy', 'nom');
        $order = $request->query->get('order', 'ASC');

        // Récupérer le repository et les clients
        $clientRepository = $entityManager->getRepository(Client::class);
        $clients = $clientRepository->findBySearchAndSortForUser(
            $user,
            !empty($search) ? $search : null,
            $sortBy,
            $order
        );

        // Générer le HTML via Twig
        $html = $this->renderView('client/pdf.html.twig', [
            'clients' => $clients,
            'search' => $search,
            'exportDate' => new \DateTime(),
        ]);

        // Configurer Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultMediaType', 'print');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Retourner le PDF en téléchargement
        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="clients_'.date('Y-m-d_H-i-s').'.pdf"',
            ]
        );
    }
}
