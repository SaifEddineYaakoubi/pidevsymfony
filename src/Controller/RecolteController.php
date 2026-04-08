<?php

namespace App\Controller;

use App\Entity\Recolte;
use App\Entity\Rendement;
use App\Entity\Recolte_archive;
use App\Form\RecolteType;
use App\Repository\RecolteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/recolte')]
class RecolteController extends AbstractController
{
    #[Route('/', name: 'app_recolte_index', methods: ['GET'])]
    public function index(RecolteRepository $recolteRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', 'date_desc');

        // Build query with search and sort
        $qb = $entityManager->createQueryBuilder();
        $qb->select('r')
           ->from(Recolte::class, 'r');

        // Add search conditions
        if (!empty($search)) {
            $qb->where('r.type_culture LIKE :search')
               ->orWhere('r.localisation LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Add sorting
        switch ($sort) {
            case 'date_desc':
                $qb->orderBy('r.date_recolte', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('r.date_recolte', 'ASC');
                break;
            case 'type_asc':
                $qb->orderBy('r.type_culture', 'ASC');
                break;
            case 'type_desc':
                $qb->orderBy('r.type_culture', 'DESC');
                break;
            default:
                $qb->orderBy('r.date_recolte', 'DESC');
        }

        $recoltes = $qb->getQuery()->getResult();

        // Get rendements for each recolte
        $rendements = [];
        foreach ($recoltes as $recolte) {
            $rendement = $entityManager->getRepository(Rendement::class)->findOneBy(['id_recolte' => $recolte->getId_recolte()]);
            $rendements[$recolte->getId_recolte()] = $rendement ? $rendement->getProductivite() : null;
        }

        return $this->render('recolte/index.html.twig', [
            'recoltes' => $recoltes,
            'rendements' => $rendements,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/new', name: 'app_recolte_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recolte = new Recolte();
        $recolte->setId_user(1); // Auto-assign default agriculteur ID
        $form = $this->createForm(RecolteType::class, $recolte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($recolte);
            $entityManager->flush();

            // Rendement calculation is now handled separately in RendementController
            // $this->calculateRendement($recolte, $entityManager);

            return $this->redirectToRoute('app_recolte_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recolte/new.html.twig', [
            'recolte' => $recolte,
            'form' => $form,
        ]);
    }

    #[Route('/{id_recolte}', name: 'app_recolte_show', methods: ['GET'])]
    public function show(Recolte $recolte, EntityManagerInterface $entityManager): Response
    {
        $rendement = $entityManager->getRepository(Rendement::class)->findOneBy(['id_recolte' => $recolte->getId_recolte()]);
        return $this->render('recolte/show.html.twig', [
            'recolte' => $recolte,
            'rendement' => $rendement,
        ]);
    }

    #[Route('/{id_recolte}/edit', name: 'app_recolte_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Recolte $recolte, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RecolteType::class, $recolte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Rendement calculation is now handled separately in RendementController
            // $this->calculateRendement($recolte, $entityManager);

            return $this->redirectToRoute('app_recolte_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recolte/edit.html.twig', [
            'recolte' => $recolte,
            'form' => $form,
        ]);
    }

    #[Route('/{id_recolte}', name: 'app_recolte_delete', methods: ['POST'])]
    public function delete(Request $request, Recolte $recolte, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$recolte->getId_recolte(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($recolte);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_recolte_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id_recolte}/rendement', name: 'app_recolte_rendement', methods: ['GET'])]
    public function showRendement(Recolte $recolte, EntityManagerInterface $entityManager): Response
    {
        $culture = $recolte->getId_culture();
        $rendement = null;
        $calculation = null;

        if ($culture) {
            $parcelle = $culture->getId_parcelle();
            if ($parcelle) {
                $surface = $parcelle->getSuperficie();
                $quantite = $recolte->getQuantite();

                // Calcul du rendement : productivité = quantité / surface
                $productivite = $surface > 0 ? $quantite / $surface : 0;

                $calculation = [
                    'quantite' => $quantite,
                    'surface' => $surface,
                    'productivite' => $productivite,
                    'productivite_formatted' => number_format($productivite, 2),
                ];

                // Chercher ou créer le rendement
                $rendementRepository = $entityManager->getRepository(Rendement::class);
                $rendement = $rendementRepository->findOneBy(['id_recolte' => $recolte->getId_recolte()]);
            }
        }

        return $this->render('recolte/rendement.html.twig', [
            'recolte' => $recolte,
            'rendement' => $rendement,
            'calculation' => $calculation,
        ]);
    }

    #[Route('/{id_recolte}/delete-confirm', name: 'app_recolte_delete_confirm', methods: ['GET', 'POST'])]
    public function deleteConfirm(Request $request, Recolte $recolte, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $cause = $request->request->get('cause_suppression');

            if (empty($cause)) {
                $this->addFlash('error', 'Veuillez indiquer la cause de suppression.');
                return $this->redirectToRoute('app_recolte_delete_confirm', ['id_recolte' => $recolte->getIdRecolte()]);
            }

            // Créer l'archive
            $archive = new Recolte_archive();
            $archive->setId_recolte_original($recolte->getIdRecolte());
            $archive->setQuantite($recolte->getQuantite());
            $archive->setDate_recolte($recolte->getDateRecolte());
            $archive->setQualite($recolte->getQualite());
            $archive->setType_culture($recolte->getTypeCulture());
            $archive->setLocalisation($recolte->getLocalisation());
            $archive->setCause_supression($cause);
            $archive->setDate_archivage(new \DateTime());
            $archive->setId_user($recolte->getIdUser());

            $entityManager->persist($archive);

            // Supprimer la récolte originale
            $entityManager->remove($recolte);
            $entityManager->flush();

            $this->addFlash('success', 'La récolte a été archivée et supprimée avec succès.');

            return $this->redirectToRoute('app_recolte_index');
        }

        return $this->render('recolte/delete_confirm.html.twig', [
            'recolte' => $recolte,
        ]);
    }

    private function calculateRendement(Recolte $recolte, EntityManagerInterface $entityManager): void
    {
        $culture = $recolte->getId_culture();
        if (!$culture) {
            return; // Skip if no culture
        }
        $parcelle = $culture->getIdParcelle();
        $surface = $parcelle->getSuperficie();
        $quantite = $recolte->getQuantite();

        $productivite = $quantite / $surface;

        // Check if Rendement exists, else create
        $rendementRepository = $entityManager->getRepository(Rendement::class);
        $rendement = $rendementRepository->findOneBy(['id_recolte' => $recolte->getId_recolte()]);

        if (!$rendement) {
            $rendement = new Rendement();
            $rendement->setId_recolte($recolte->getId_recolte());
        }

        $rendement->setSurface_exploitee($surface);
        $rendement->setQuantite_totale($quantite);
        $rendement->setProductivite($productivite);

        $entityManager->persist($rendement);
        $entityManager->flush();
    }
}
