<?php

namespace App\Controller;

use App\Entity\Recolte;
use App\Entity\Rendement;
use App\Entity\Recolte_archive;
use App\Entity\Utilisateur;
use App\Form\RecolteType;
use App\Repository\RecolteRepository;
use App\Service\SoilAnalysisService;
use App\Service\HuggingFaceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/recolte')]
class RecolteController extends AbstractController
{
    #[Route('/statistiques', name: 'app_recolte_statistiques', methods: ['GET'])]
    public function statistiques(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $recoltes = $entityManager->getRepository(Recolte::class)->findBy(
            ['id_user' => $user->getIdUser()],
            ['date_recolte' => 'DESC']
        );

        $statsParMois = [];
        foreach ($recoltes as $recolte) {
            if ($recolte->getDateRecolte()) {
                $mois = (int)$recolte->getDateRecolte()->format('m');
                if (!isset($statsParMois[$mois])) {
                    $statsParMois[$mois] = 0;
                }
                $statsParMois[$mois]++;
            }
        }

        arsort($statsParMois);

        $labels = [];
        $data = [];
        $statistiques = [];
        $moisNoms = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        foreach ($statsParMois as $mois => $nombre) {
            $labels[] = $moisNoms[$mois] ?? 'Mois ' . $mois;
            $data[] = $nombre;
            $statistiques[] = [
                'mois' => $mois,
                'nombre_recoltes' => $nombre
            ];
        }

        return $this->render('recolte/statistiques.html.twig', [
            'labels' => json_encode($labels),
            'data' => json_encode($data),
            'statistiques' => $statistiques,
            'moisNoms' => $moisNoms,
        ]);
    }

    #[Route('/', name: 'app_recolte_index', methods: ['GET'])]
    public function index(RecolteRepository $recolteRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $search = $request->query->getString('search', '');
        $sort = $request->query->getString('sort', 'date_desc');

        $recoltes = $recolteRepository->findForIndexForUser($user, $search, $sort);
        $stats = $recolteRepository->getIndexStatsForUser($user, $search);

        // Get rendements for each recolte (map recolteId => productivite)
        $rendements = [];
        if (count($recoltes) > 0) {
            $ids = array_map(static fn(Recolte $r) => $r->getId_recolte(), $recoltes);
            $qbR = $entityManager->createQueryBuilder();
            $qbR->select('re', 'r')
                ->from(Rendement::class, 're')
                ->leftJoin('re.id_recolte', 'r')
                ->andWhere('r.id_recolte IN (:ids)')
                ->setParameter('ids', $ids);
            /** @var Rendement[] $rends */
            $rends = $qbR->getQuery()->getResult();
            foreach ($rends as $rend) {
                $recolte = $rend->getId_recolte();
                if ($recolte) {
                    $rendements[$recolte->getId_recolte()] = $rend->getProductivite();
                }
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('recolte/_results.html.twig', [
                'recoltes' => $recoltes,
                'rendements' => $rendements,
                'stats' => $stats,
                'search' => $search,
                'sort' => $sort,
            ]);
        }

        return $this->render('recolte/index.html.twig', [
            'recoltes' => $recoltes,
            'rendements' => $rendements,
            'stats' => $stats,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/new', name: 'app_recolte_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $recolte = new Recolte();
        $recolte->setUtilisateur($user);
        $form = $this->createForm(RecolteType::class, $recolte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($recolte);
            $entityManager->flush();

            return $this->redirectToRoute('app_recolte_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recolte/new.html.twig', [
            'recolte' => $recolte,
            'form' => $form,
        ]);
    }

    #[Route('/{id_recolte}', name: 'app_recolte_show', methods: ['GET'])]
    public function show(int $id_recolte, RecolteRepository $recolteRepository, EntityManagerInterface $entityManager, SoilAnalysisService $soilService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $recolte = $recolteRepository->findOneForUser($id_recolte, $user);

        if (!$recolte) {
            throw $this->createNotFoundException('Récolte non trouvée');
        }

        $rendement = $entityManager->getRepository(Rendement::class)->findOneBy(['id_recolte' => $recolte->getId_recolte()]);

        // Get soil analysis for this recolte
        $soilAnalysis = $soilService->getSoilAnalysisForRecolte($recolte);

        return $this->render('recolte/show.html.twig', [
            'recolte' => $recolte,
            'rendement' => $rendement,
            'soil_analysis' => $soilAnalysis,
            'json_pretty' => json_encode($soilAnalysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ]);
    }

    #[Route('/{id_recolte}/edit', name: 'app_recolte_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id_recolte, RecolteRepository $recolteRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $recolte = $recolteRepository->findOneForUser($id_recolte, $user);

        if (!$recolte) {
            throw $this->createNotFoundException('Récolte non trouvée');
        }

        $form = $this->createForm(RecolteType::class, $recolte);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_recolte_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recolte/edit.html.twig', [
            'recolte' => $recolte,
            'form' => $form,
        ]);
    }

    #[Route('/{id_recolte}', name: 'app_recolte_delete', methods: ['POST'])]
    public function delete(Request $request, int $id_recolte, RecolteRepository $recolteRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $recolte = $recolteRepository->findOneForUser($id_recolte, $user);

        if (!$recolte) {
            throw $this->createNotFoundException('Récolte non trouvée');
        }

        if ($this->isCsrfTokenValid('delete'.$recolte->getId_recolte(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($recolte);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_recolte_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id_recolte}/rendement', name: 'app_recolte_rendement', methods: ['GET'])]
    public function showRendement(int $id_recolte, RecolteRepository $recolteRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $recolte = $recolteRepository->findOneForUser($id_recolte, $user);

        if (!$recolte) {
            throw $this->createNotFoundException('Récolte non trouvée');
        }

        $culture = $recolte->getId_culture();
        $rendement = null;
        $calculation = null;

        if ($culture) {
            $parcelle = $culture->getId_parcelle();
            if ($parcelle) {
                $surface = $parcelle->getSuperficie();
                $quantite = $recolte->getQuantite();

                $productivite = $surface > 0 ? $quantite / $surface : 0;

                $calculation = [
                    'quantite' => $quantite,
                    'surface' => $surface,
                    'productivite' => $productivite,
                    'productivite_formatted' => number_format($productivite, 2),
                ];

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
    public function deleteConfirm(Request $request, int $id_recolte, RecolteRepository $recolteRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $recolte = $recolteRepository->findOneForUser($id_recolte, $user);

        if (!$recolte) {
            throw $this->createNotFoundException('Récolte non trouvée');
        }

        if ($request->isMethod('POST')) {
            $cause = $request->request->get('cause_suppression');

            if (empty($cause)) {
                $this->addFlash('error', 'Veuillez indiquer la cause de suppression.');
                return $this->redirectToRoute('app_recolte_delete_confirm', ['id_recolte' => $recolte->getIdRecolte()]);
            }

            $archive = new Recolte_archive();
            $archive->setId_recolte_original($recolte->getIdRecolte());
            $archive->setQuantite($recolte->getQuantite());
            $archive->setDate_recolte($recolte->getDateRecolte());
            $archive->setQualite($recolte->getQualite());
            $archive->setType_culture($recolte->getTypeCulture());
            $archive->setLocalisation($recolte->getLocalisation());
            $archive->setCause_supression($cause);
            $archive->setDate_archivage(new \DateTime());
            $archive->setId_user($user->getIdUser());

            $entityManager->persist($archive);
            $entityManager->remove($recolte);
            $entityManager->flush();

            $this->addFlash('success', 'La récolte a été archivée et supprimée avec succès.');

            return $this->redirectToRoute('app_recolte_index');
        }

        return $this->render('recolte/delete_confirm.html.twig', [
            'recolte' => $recolte,
        ]);
    }

    #[Route('/api/predict', name: 'app_recolte_api_predict', methods: ['POST'])]
    public function apiPredict(Request $request, HuggingFaceService $huggingFaceService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['surface'], $data['quantite'], $data['typeCulture'])) {
            return $this->json(['error' => 'Données invalides. Fournir surface, quantite et typeCulture.'], 400);
        }

        $prediction = $huggingFaceService->predict([
            'surface' => (int) $data['surface'],
            'quantite' => (int) $data['quantite'],
            'typeCulture' => (string) $data['typeCulture'],
        ]);

        if (isset($prediction['error'])) {
            return $this->json($prediction, 500);
        }

        return $this->json($prediction);
    }

    #[Route('/{id_recolte}/export-csv', name: 'app_recolte_export_csv', methods: ['GET'])]
    public function exportCsv(int $id_recolte, RecolteRepository $recolteRepository, HuggingFaceService $huggingFaceService): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $recolte = $recolteRepository->findOneForUser($id_recolte, $user);
        if (!$recolte) {
            throw $this->createNotFoundException('Récolte non trouvée');
        }

        // Get prediction data
        $surface = $recolte->getIdCulture()?->getIdParcelle()?->getSuperficie() ?? 10;
        $predictionData = $huggingFaceService->predict([
            'surface' => $surface,
            'quantite' => $recolte->getQuantite(),
            'typeCulture' => $recolte->getTypeCulture(),
        ]);

        // Build CSV content manually (no external library needed)
        $rows = [];
        $rows[] = ['Champ', 'Valeur', 'Description'];
        $rows[] = ['ID Récolte', '#' . $recolte->getIdRecolte(), 'Identifiant unique'];
        $rows[] = ['Quantité', $recolte->getQuantite() . ' kg', 'Quantité récoltée'];
        $rows[] = ['Type de Culture', $recolte->getTypeCulture(), 'Type de culture'];
        $rows[] = ['Localisation', $recolte->getLocalisation(), 'Emplacement'];
        $rows[] = ['Surface', $surface . ' m²', 'Surface cultivée'];
        $rows[] = ['Date de Récolte', $recolte->getDateRecolte() ? $recolte->getDateRecolte()->format('d/m/Y') : 'N/A', 'Date'];
        $rows[] = ['', '', ''];
        $rows[] = ['=== PRÉDICTIONS IA ===', '', ''];

        if (!isset($predictionData['error'])) {
            $rows[] = ['Rendement Prédit', ($predictionData['predictionRendement'] ?? 'N/A') . ' unités/ha', 'Estimation rendement'];
            $rows[] = ['Score Qualité', ($predictionData['scoreQualite'] ?? 'N/A') . '/10', 'Score qualité'];

            if (isset($predictionData['propositionIrrigation']) && is_array($predictionData['propositionIrrigation'])) {
                $rows[] = ['Irrigation - Statut', $predictionData['propositionIrrigation']['statut'] ?? 'N/A', ''];
                $rows[] = ['Irrigation - Besoins', ($predictionData['propositionIrrigation']['besoins_mm'] ?? 'N/A') . ' mm', ''];
                $rows[] = ['Irrigation - Action', $predictionData['propositionIrrigation']['action'] ?? 'N/A', ''];
            }

            if (isset($predictionData['recommandationEngrais']) && is_array($predictionData['recommandationEngrais'])) {
                $rows[] = ['Engrais - Type', $predictionData['recommandationEngrais']['type_principal'] ?? 'N/A', ''];
                $rows[] = ['Engrais - Dosage', ($predictionData['recommandationEngrais']['dosage_kg_ha'] ?? 'N/A') . ' kg/ha', ''];
            }

            $rows[] = ['Conseil IA', $predictionData['conseilOptimisation'] ?? 'N/A', 'Recommandation'];
        } else {
            $rows[] = ['Erreur', $predictionData['error'] ?? 'Erreur inconnue', ''];
        }

        $rows[] = ['', '', ''];
        $rows[] = ['Généré le', date('d/m/Y H:i:s'), ''];

        // Convert to CSV string
        $csvContent = '';
        foreach ($rows as $row) {
            $csvContent .= implode(';', array_map(function($cell) {
                return '"' . str_replace('"', '""', (string)$cell) . '"';
            }, $row)) . "\n";
        }

        $filename = 'prediction_recolte_' . $recolte->getIdRecolte() . '_' . date('Y-m-d') . '.csv';

        return new Response(
            "\xEF\xBB\xBF" . $csvContent, // BOM for Excel UTF-8
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'no-cache',
            ]
        );
    }
}
