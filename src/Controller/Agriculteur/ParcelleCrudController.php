<?php

namespace App\Controller\Agriculteur;

use App\Entity\Parcelle;
use App\Form\ParcelleType;
use App\Repository\ParcelleRepository;
use App\Service\Validation\SymfonyEntityValidator;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/agriculteur/parcelles', name: 'agri_parcelle_')]
final class ParcelleCrudController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ParcelleRepository $repo): Response
    {
        $q = $request->query->getString('q');
        $sort = $request->query->getString('sort') ?: 'nom';
        $dir = $request->query->getString('dir') ?: 'ASC';
        $parcelles = $repo->searchByQuery($q, $sort, $dir);
        $totalAll = $repo->count([]);
        $countsByEtat = $repo->countByEtat($q);

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
        $parcelle = new Parcelle();

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
        $parcelle = $repo->find($id);
        if (!$parcelle) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ParcelleType::class, $parcelle);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $errors = $validator->validate($parcelle);
            if ($form->isValid() && $errors === []) {
                $em->flush();
                $this->addFlash('success', 'Parcelle modifiée avec succès.');
                return $this->redirectToRoute('agri_parcelle_index');
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

        $parcelle = $repo->find($id);
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

