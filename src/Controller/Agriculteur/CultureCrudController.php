<?php

namespace App\Controller\Agriculteur;

use App\Entity\Culture;
use App\Form\CultureType;
use App\Repository\CultureRepository;
use App\Repository\ParcelleRepository;
use App\Service\Validation\SymfonyEntityValidator;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/agriculteur/cultures', name: 'agri_culture_')]
final class CultureCrudController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, CultureRepository $repo): Response
    {
        $q = $request->query->getString('q');
        $sort = $request->query->getString('sort') ?: 'plantation';
        $dir = $request->query->getString('dir') ?: 'DESC';
        $cultures = $repo->searchByQuery($q, $sort, $dir);
        $totalAll = $repo->count([]);
        $countsByEtat = $repo->countByEtatCroissance($q);

        // convenience: maturite count for the CURRENT filter
        $maturiteFiltered = $countsByEtat['maturite'] ?? 0;

        if ($request->isXmlHttpRequest()) {
            return $this->render('agriculteur/culture/_results.html.twig', [
                'cultures' => $cultures,
                'q' => $q,
                'sort' => $sort,
                'dir' => $dir,
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
        $culture = new Culture();
        $form = $this->createForm(CultureType::class, $culture);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = $validator->validate($culture);
            if ($form->isValid() && $errors === []) {
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
        $culture = $repo->find($id);
        if (!$culture) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(CultureType::class, $culture);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = $validator->validate($culture);
            if ($form->isValid() && $errors === []) {
                $em->flush();
                $this->addFlash('success', 'Culture modifiée avec succès.');
                return $this->redirectToRoute('agri_culture_index');
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

        $culture = $repo->find($id);
        if (!$culture) {
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

