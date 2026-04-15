<?php

namespace App\Controller\Agriculteur;

use App\Entity\Parcelle;
use App\Entity\Utilisateur;
use App\Form\ParcelleType;
use App\Repository\ParcelleRepository;
use App\Service\Api\WeatherService;
use App\Service\Validation\SymfonyEntityValidator;
use App\Service\Pdf\PdfExporter;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/agriculteur/parcelles', name: 'agri_parcelle_')]
final class ParcelleCrudController extends AbstractController
{
    #[Route('/{id}/weather', name: 'weather', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function weather(int $id, ParcelleRepository $repo, WeatherService $weatherService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $parcelle = $repo->findOneForUser($id, $user);
        if (!$parcelle) {
            throw $this->createNotFoundException();
        }

        $lat = method_exists($parcelle, 'getLatitude') ? $parcelle->getLatitude() : null;
        $lon = method_exists($parcelle, 'getLongitude') ? $parcelle->getLongitude() : null;
        if ($lat !== null && $lon !== null) {
            $weather = $weatherService->getCurrentWeatherByCoordinates((float) $lat, (float) $lon);
        } else {
            $weather = $weatherService->getCurrentWeatherByCity((string) $parcelle->getLocalisation());
        }

        return $this->json([
            'ok' => true,
            'parcelle' => [
                'id' => $parcelle->getId_parcelle(),
                'nom' => $parcelle->getNom(),
                'localisation' => $parcelle->getLocalisation(),
                'latitude' => $lat,
                'longitude' => $lon,
            ],
            'weather' => $weather,
        ]);
    }

    #[Route('/export/pdf', name: 'export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, ParcelleRepository $repo, PdfExporter $pdfExporter): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $q = $request->query->getString('q');
        $sort = $request->query->getString('sort') ?: 'nom';
        $dir = $request->query->getString('dir') ?: 'ASC';

        $parcelles = $repo->searchByQueryForUser($user, $q, $sort, $dir);

        $html = $this->renderView('agriculteur/parcelle/export_pdf.html.twig', [
            'parcelles' => $parcelles,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
            'exportedAt' => new \DateTimeImmutable(),
        ]);

        $pdf = $pdfExporter->renderHtmlToPdf($html, 'A4', 'portrait');

        $filename = 'parcelles_' . (new \DateTimeImmutable())->format('Ymd_His') . '.pdf';
        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    #[Route('/{id}/show', name: 'show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(int $id, ParcelleRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $parcelle = $repo->findOneForUser($id, $user);
        if (!$parcelle) {
            throw $this->createNotFoundException();
        }

        return $this->render('agriculteur/parcelle/show.html.twig', [
            'parcelle' => $parcelle,
        ]);
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ParcelleRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $q = $request->query->getString('q');
        $sort = $request->query->getString('sort') ?: 'nom';
        $dir = $request->query->getString('dir') ?: 'ASC';

        if ($isAdmin) {
            $parcelles = $repo->searchByQuery($q, $sort, $dir);
            $totalAll = $repo->count([]);
            $countsByEtat = $repo->countByEtat($q);
        } else {
            $parcelles = $repo->searchByQueryForUser($user, $q, $sort, $dir);
            $totalAll = $repo->count(['id_user' => $user]);
            $countsByEtat = $repo->countByEtatForUser($user, $q);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('agriculteur/parcelle/_results.html.twig', [
                'parcelles' => $parcelles,
                'q' => $q,
                'sort' => $sort,
                'dir' => $dir,
                'stats' => [
                    'total_all' => $totalAll,
                    'total_filtered' => count($parcelles),
                    'counts_by_etat' => $countsByEtat,
                ],
            ]);
        }

        return $this->render('agriculteur/parcelle/index.html.twig', [
            'parcelles' => $parcelles,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
            'stats' => [
                'total_all' => $totalAll,
                'total_filtered' => count($parcelles),
                'counts_by_etat' => $countsByEtat,
            ],
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SymfonyEntityValidator $validator): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $parcelle = new Parcelle();
        $parcelle->setId_user($user);

        $form = $this->createForm(ParcelleType::class, $parcelle);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = $validator->validate($parcelle);
            if ($form->isValid() && $errors === []) {
                $em->persist($parcelle);
                $em->flush();
                $this->addFlash('success', 'Parcelle ajoutée avec succès.');
                return $this->redirectToRoute('agri_parcelle_index');
            }

            return $this->render('agriculteur/parcelle/form_symfony.html.twig', [
                'mode' => 'new',
                'form' => $form,
                'extraErrors' => $errors,
            ]);
        }

        return $this->render('agriculteur/parcelle/form_symfony.html.twig', [
            'mode' => 'new',
            'form' => $form,
            'extraErrors' => [],
        ]);
    }

    #[Route('/{id}', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, ParcelleRepository $repo, EntityManagerInterface $em, SymfonyEntityValidator $validator): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $parcelle = $repo->findOneForUser($id, $user);
        if (!$parcelle) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ParcelleType::class, $parcelle);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = $validator->validate($parcelle);
            if ($form->isValid() && $errors === []) {
                // Safety: never flush if required fields are missing (prevents SQL NOT NULL violations)
                if ($parcelle->getNom() === '' || $parcelle->getSuperficie() === null || $parcelle->getLocalisation() === '' || $parcelle->getEtat() === '') {
                    $this->addFlash('error', 'Certains champs obligatoires sont manquants.');
                } else {
                $em->flush();
                $this->addFlash('success', 'Parcelle modifiée avec succès.');
                return $this->redirectToRoute('agri_parcelle_index');
                }
            }

            return $this->render('agriculteur/parcelle/form_symfony.html.twig', [
                'mode' => 'edit',
                'form' => $form,
                'extraErrors' => $errors,
            ]);
        }

        return $this->render('agriculteur/parcelle/form_symfony.html.twig', [
            'mode' => 'edit',
            'form' => $form,
            'extraErrors' => [],
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, ParcelleRepository $repo, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_parcelle_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $parcelle = $repo->findOneForUser($id, $user);
        if (!$parcelle) {
            throw $this->createNotFoundException();
        }

        // Business rule: refuse delete if dependencies
        if ($parcelle->getCultures()->count() > 0) {
            $this->addFlash('error', 'Suppression refusée: cette parcelle contient des cultures.');
            return $this->redirectToRoute('agri_parcelle_index');
        }

        try {
            $em->remove($parcelle);
            $em->flush();
            $this->addFlash('success', 'Parcelle supprimée.');
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', 'Suppression refusée: cette parcelle est liée à d\'autres données.');
        }

        return $this->redirectToRoute('agri_parcelle_index');
    }
}

