<?php

namespace App\Controller\Agriculteur;

use App\Entity\Culture;
use App\Entity\Utilisateur;
use App\Form\CultureType;
use App\Repository\CultureRepository;
use App\Repository\ParcelleRepository;
use App\Service\Alert\CultureAlertService;
use App\Service\Pdf\PdfExporter;
use App\Service\Validation\SymfonyEntityValidator;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/agriculteur/cultures', name: 'agri_culture_')]
final class CultureCrudController extends AbstractController
{
    #[Route('/calendar/events', name: 'calendar_events', methods: ['GET'])]
    public function calendarEvents(CultureRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $cultures = $isAdmin
            ? $repo->searchByQuery(null, null, null)
            : $repo->searchByQueryForUser($user, null, null, null);

        $etatColors = [
            'germination' => '#6f42c1',
            'croissance'  => '#198754',
            'floraison'   => '#fd7e14',
            'maturite'    => '#dc3545',
        ];

        $events = [];
        foreach ($cultures as $culture) {
            $color = $etatColors[$culture->getEtatCroissance()] ?? '#6c757d';
            $parcelleNom = $culture->getParcelle() ? $culture->getParcelle()->getNom() : '—';

            // Plantation event
            if ($culture->getDatePlantation()) {
                $events[] = [
                    'id'              => 'plantation_' . $culture->getId_culture(),
                    'title'           => '🌱 ' . $culture->getTypeCulture() . ' (' . $parcelleNom . ')',
                    'start'           => $culture->getDatePlantation()->format('Y-m-d'),
                    'backgroundColor' => '#10b981',
                    'borderColor'     => '#059669',
                    'textColor'       => '#ffffff',
                    'extendedProps'   => [
                        'type'      => 'plantation',
                        'culture'   => $culture->getTypeCulture(),
                        'parcelle'  => $parcelleNom,
                        'etat'      => $culture->getEtatCroissance(),
                        'url'       => $this->generateUrl('agri_culture_show', ['id' => $culture->getId_culture()]),
                    ],
                ];
            }

            // Récolte prévue event
            if ($culture->getDateRecoltePrevue()) {
                $events[] = [
                    'id'              => 'recolte_' . $culture->getId_culture(),
                    'title'           => '🌾 ' . $culture->getTypeCulture() . ' (' . $parcelleNom . ')',
                    'start'           => $culture->getDateRecoltePrevue()->format('Y-m-d'),
                    'backgroundColor' => $color,
                    'borderColor'     => $color,
                    'textColor'       => '#ffffff',
                    'extendedProps'   => [
                        'type'      => 'recolte',
                        'culture'   => $culture->getTypeCulture(),
                        'parcelle'  => $parcelleNom,
                        'etat'      => $culture->getEtatCroissance(),
                        'url'       => $this->generateUrl('agri_culture_show', ['id' => $culture->getId_culture()]),
                    ],
                ];
            }
        }

        return $this->json($events);
    }

    #[Route('/export/pdf', name: 'export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, CultureRepository $repo, PdfExporter $pdfExporter): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $q = $request->query->getString('q');
        $sort = $request->query->getString('sort') ?: 'plantation';
        $dir = $request->query->getString('dir') ?: 'DESC';

        $cultures = $repo->searchByQueryForUser($user, $q, $sort, $dir);

        $html = $this->renderView('agriculteur/culture/export_pdf.html.twig', [
            'cultures' => $cultures,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
            'exportedAt' => new \DateTimeImmutable(),
        ]);

        $pdf = $pdfExporter->renderHtmlToPdf($html, 'A4', 'landscape');

        $filename = 'cultures_' . (new \DateTimeImmutable())->format('Ymd_His') . '.pdf';
        return new Response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    #[Route('/{id}/show', name: 'show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(int $id, CultureRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $culture = $repo->find($id);
        if (!$culture) {
            throw $this->createNotFoundException();
        }

        // Ownership check via parcelle (admin can view all)
        if (!$isAdmin && ($culture->getParcelle() === null || $culture->getParcelle()->getId_user() !== $user)) {
            throw $this->createNotFoundException();
        }

        return $this->render('agriculteur/culture/show.html.twig', [
            'culture' => $culture,
        ]);
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, CultureRepository $repo, CultureAlertService $alertService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $q = $request->query->getString('q');
        $sort = $request->query->getString('sort') ?: 'plantation';
        $dir = $request->query->getString('dir') ?: 'DESC';

        if ($isAdmin) {
            $cultures = $repo->searchByQuery($q, $sort, $dir);
            $totalAll = count($repo->searchByQuery(null, null, null));
        } else {
            $cultures = $repo->searchByQueryForUser($user, $q, $sort, $dir);
            $totalAll = count($repo->searchByQueryForUser($user, null, null, null));
        }

        // Calcul dynamique des états pour être 100% synchronisé avec l'affichage (qui utilise PostLoad)
        $countsByEtat = [
            'germination' => 0,
            'croissance' => 0,
            'floraison' => 0,
            'maturite' => 0
        ];
        foreach ($cultures as $c) {
            $etat = $c->getEtatCroissance();
            if (isset($countsByEtat[$etat])) {
                $countsByEtat[$etat]++;
            } else {
                $countsByEtat[$etat] = 1;
            }
        }

        // Alerts module: harvest due soon (< 7 days)
        $alerts = $alertService->getHarvestDueSoonAlerts($cultures);

        // convenience: maturite count for the CURRENT filter
        $maturiteFiltered = $countsByEtat['maturite'] ?? 0;

        if ($request->isXmlHttpRequest()) {
            return $this->render('agriculteur/culture/_results.html.twig', [
                'cultures' => $cultures,
                'q' => $q,
                'sort' => $sort,
                'dir' => $dir,
                'alerts' => $alerts,
                'stats' => [
                    'total_all' => $totalAll,
                    'total_filtered' => count($cultures),
                    'counts_by_etat' => $countsByEtat,
                    'maturite' => $maturiteFiltered,
                ],
            ]);
        }

        return $this->render('agriculteur/culture/index.html.twig', [
            'cultures' => $cultures,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
            'alerts' => $alerts,
            'stats' => [
                'total_all' => $totalAll,
                'total_filtered' => count($cultures),
                'counts_by_etat' => $countsByEtat,
                'maturite' => $maturiteFiltered,
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

        $culture = new Culture();
        $form = $this->createForm(CultureType::class, $culture, [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Mettre à jour l'état de croissance automatiquement avant la validation
            $culture->updateEtatCroissanceAuto();
            
            $errors = $validator->validate($culture);
            if ($form->isValid() && $errors === []) {
                // Ownership guard: the selected parcelle must belong to the current user.
                if ($culture->getParcelle() === null || $culture->getParcelle()->getId_user() !== $user) {
                    throw $this->createAccessDeniedException();
                }

                $parcelle = $culture->getParcelle();
                if ($parcelle->getEtat() === 'repos') {
                    $parcelle->setEtat('active');
                    $em->persist($parcelle);
                }

                $em->persist($culture);
                $em->flush();
                $this->addFlash('success', 'Culture ajoutée avec succès.');
                return $this->redirectToRoute('agri_culture_index');
            }

            return $this->render('agriculteur/culture/form_symfony.html.twig', [
                'mode' => 'new',
                'form' => $form,
                'extraErrors' => $errors,
            ]);
        }

        return $this->render('agriculteur/culture/form_symfony.html.twig', [
            'mode' => 'new',
            'form' => $form,
            'extraErrors' => [],
        ]);
    }

    #[Route('/{id}', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, CultureRepository $repo, EntityManagerInterface $em, SymfonyEntityValidator $validator): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $culture = $repo->find($id);
        if (!$culture) {
            throw $this->createNotFoundException();
        }

        if ($culture->getParcelle() === null || $culture->getParcelle()->getId_user() !== $user) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(CultureType::class, $culture, [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Mettre à jour l'état de croissance automatiquement avant la validation
            $culture->updateEtatCroissanceAuto();
            
            $errors = $validator->validate($culture);
            if ($form->isValid() && $errors === []) {
                // Safety: never flush if required fields are missing (prevents SQL NOT NULL violations)
                if ($culture->getParcelle() === null || $culture->getTypeCulture() === '' || $culture->getDatePlantation() === null || $culture->getDateRecoltePrevue() === null || $culture->getEtatCroissance() === '') {
                    $this->addFlash('error', 'Certains champs obligatoires sont manquants.');
                } else {
                    $parcelle = $culture->getParcelle();
                    if ($parcelle && $parcelle->getEtat() === 'repos') {
                        $parcelle->setEtat('active');
                        $em->persist($parcelle);
                    }

                    $em->flush();
                    $this->addFlash('success', 'Culture modifiée avec succès.');
                    return $this->redirectToRoute('agri_culture_index');
                }
            }

            return $this->render('agriculteur/culture/form_symfony.html.twig', [
                'mode' => 'edit',
                'form' => $form,
                'extraErrors' => $errors,
            ]);
        }

        return $this->render('agriculteur/culture/form_symfony.html.twig', [
            'mode' => 'edit',
            'form' => $form,
            'extraErrors' => [],
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request, CultureRepository $repo, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_culture_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $culture = $repo->find($id);
        if (!$culture) {
            throw $this->createNotFoundException();
        }

        if ($culture->getParcelle() === null || $culture->getParcelle()->getId_user() !== $user) {
            throw $this->createNotFoundException();
        }

        // Business rule: refuse delete if dependencies
        if ($culture->getRecoltes()->count() > 0) {
            $this->addFlash('error', 'Suppression refusée: cette culture possède des récoltes.');
            return $this->redirectToRoute('agri_culture_index');
        }

        try {
            $em->remove($culture);
            $em->flush();
            $this->addFlash('success', 'Culture supprimée.');
        } catch (ForeignKeyConstraintViolationException) {
            $this->addFlash('error', 'Suppression refusée: cette culture est liée à d\'autres données.');
        }

        return $this->redirectToRoute('agri_culture_index');
    }
}

