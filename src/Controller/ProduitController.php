<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use App\Service\PexelsImageService;
use App\Service\UsdaNutritionService;
use App\Service\UsdaAmsService;

#[Route('/produit')]
class ProduitController extends AbstractController
{
    private Environment $twig;
    private UsdaAmsService $usdaAmsService;

    public function __construct(Environment $twig, UsdaAmsService $usdaAmsService)
    {
        $this->twig = $twig;
        $this->usdaAmsService = $usdaAmsService;
    }

    #[Route('/', name: 'produit_index', methods: ['GET'])]
    public function index(Request $request, ProduitRepository $produitRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $searchField = (string) $request->query->get('searchField', 'nom');
        $sort = (string) $request->query->get('sort', 'idProduit');
        $direction = strtoupper((string) $request->query->get('direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();

        // Responsable stock voit uniquement ses propres produits
        if ($user instanceof \App\Entity\Utilisateur && $user->getRole() === 'responsable_stock') {
            $produits = $produitRepository->findBySearchAndSortForUser($user, $search, $searchField, $sort, $direction);
        } else {
            $produits = $produitRepository->findBySearchAndSort($search, $searchField, $sort, $direction);
        }

        $sortOptions = [
            'idProduit' => 'ID',
            'nom' => 'Nom',
            'type' => 'Type',
            'unite' => 'Unité',
            'prixUnitaire' => 'Prix',
            'idUser' => 'Utilisateur',
        ];

        $searchOptions = [
            'nom' => 'Nom',
            'type' => 'Type',
            'unite' => 'Unité',
        ];

        return $this->render('produit/index.html.twig', [
            'produits' => $produits,
            'search' => $search,
            'searchField' => $searchField,
            'sort' => $sort,
            'direction' => $direction,
            'sortOptions' => $sortOptions,
            'searchOptions' => $searchOptions,
        ]);
    }

    #[Route('/new', name: 'produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Assigner l'utilisateur connecté
            $user = $this->getUser();
            if ($user instanceof \App\Entity\Utilisateur) {
                $produit->setUtilisateur($user);
            }

            $em->persist($produit);
            $em->flush();

            return $this->redirectToRoute('produit_index');
        }

        return $this->render('produit/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/export-pdf', name: 'produit_export_pdf', methods: ['GET'])]
    public function exportPdf(ProduitRepository $produitRepository): Response
    {
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();

        // Responsable stock exporte uniquement ses propres produits
        if ($user instanceof \App\Entity\Utilisateur && $user->getRole() === 'responsable_stock') {
            $produits = $produitRepository->findBySearchAndSortForUser($user, null, null);
        } else {
            $produits = $produitRepository->findAll();
        }

        $html = $this->twig->render('produit/export-pdf.html.twig', [
            'produits' => $produits,
            'generatedDate' => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('isRtl', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="produits_' . date('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    #[Route('/get-market-price-from-name', name: 'produit_get_market_price_from_name', methods: ['GET'])]
    public function getMarketPriceFromName(Request $request): JsonResponse
    {
        $productName = $request->query->get('name', '');

        if (!$productName) {
            return $this->json([
                'status' => 'not_found',
                'message' => 'Nom du produit requis'
            ]);
        }

        $prixData = $this->usdaAmsService->getMarketPrice($productName);

        if ($prixData) {
            return $this->json($prixData);
        } else {
            return $this->json([
                'status' => 'not_found',
                'message' => 'Prix non disponible pour ce produit'
            ]);
        }
    }

    #[Route('/{id_produit}', name: 'produit_show', methods: ['GET'])]
    public function show(int $id_produit, ProduitRepository $repo): Response
    {
        $produit = $repo->find($id_produit);
        if (!$produit) {
            throw $this->createNotFoundException('Produit not found');
        }

        $stockQuantity = 0.0;
        foreach ($produit->getStocks() as $stock) {
            $stockQuantity += $stock->getQuantite() ?? 0.0;
        }

        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
            'stockQuantity' => $stockQuantity,
            'stockLimit' => 5,
        ]);
    }

    #[Route('/{id_produit}/image', name: 'produit_image', methods: ['GET'])]
    public function getImage(int $id_produit, ProduitRepository $repo, PexelsImageService $pexelsImageService): JsonResponse
    {
        $produit = $repo->find($id_produit);
        if (!$produit) {
            return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $searchLabel = $produit->getNom() ?: $produit->getType() ?: '';
        $imageData = $pexelsImageService->searchProductImageDetails($searchLabel);

        return $this->json([
            'imageUrl' => $imageData['url'] ?? null,
            'photographer' => $imageData['photographer'] ?? null,
            'photographerUrl' => $imageData['photographerUrl'] ?? null,
        ]);
    }

    #[Route('/{id_produit}/refresh-image', name: 'produit_refresh_image', methods: ['GET'])]
    public function refreshImage(int $id_produit, ProduitRepository $repo, PexelsImageService $pexelsImageService): JsonResponse
    {
        $produit = $repo->find($id_produit);
        if (!$produit) {
            return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $searchLabel = $produit->getNom() ?: $produit->getType() ?: '';
        $pexelsImageService->clearProductImageCache($searchLabel);
        $imageData = $pexelsImageService->searchProductImageDetails($searchLabel);

        return $this->json([
            'imageUrl' => $imageData['url'] ?? null,
            'photographer' => $imageData['photographer'] ?? null,
            'photographerUrl' => $imageData['photographerUrl'] ?? null,
        ]);
    }

    #[Route('/{id_produit}/nutrition', name: 'produit_nutrition', methods: ['GET'])]
    public function getNutrition(int $id_produit, ProduitRepository $repo, UsdaNutritionService $usdaNutritionService): JsonResponse
    {
        $produit = $repo->find($id_produit);
        if (!$produit) {
            return $this->json(['error' => 'Produit non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $nutritionData = $usdaNutritionService->getNutrition($produit->getNom() ?? '');

        return $this->json($nutritionData ?? ['error' => 'Données nutritionnelles non disponibles']);
    }

    #[Route('/{id_produit}/prix-marche', name: 'produit_prix_marche', methods: ['GET'])]
    public function getPrixMarche(int $id_produit, ProduitRepository $repo): JsonResponse
    {
        $produit = $repo->find($id_produit);
        if (!$produit) {
            return $this->json([
                'status' => 'not_found',
                'message' => 'Produit non trouvé'
            ], Response::HTTP_NOT_FOUND);
        }

        $prixData = $this->usdaAmsService->getMarketPrice($produit->getNom() ?? '');

        if ($prixData) {
            return $this->json($prixData);
        } else {
            return $this->json([
                'status' => 'not_found',
                'message' => 'Prix non disponible pour ce produit'
            ]);
        }
    }

    #[Route('/{id_produit}/edit', name: 'produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id_produit, ProduitRepository $repo, EntityManagerInterface $em): Response
    {
        $produit = $repo->find($id_produit);
        if (!$produit) {
            throw $this->createNotFoundException('Produit not found');
        }
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('produit_index');
        }

        return $this->render('produit/edit.html.twig', [
            'form' => $form->createView(),
            'produit' => $produit,
        ]);
    }

    #[Route('/{id_produit}/delete', name: 'produit_delete', methods: ['POST'])]
    public function delete(Request $request, int $id_produit, ProduitRepository $repo, EntityManagerInterface $em): Response
    {
        $produit = $repo->find($id_produit);
        if (!$produit) {
            throw $this->createNotFoundException('Produit not found');
        }
        if ($this->isCsrfTokenValid('delete' . $produit->getId_produit(), (string) $request->request->get('_token'))) {
            // Supprimer toutes les entrées de stock associées à ce produit
            $stockRepository = $em->getRepository(\App\Entity\Stock::class);
            $stocks = $stockRepository->createQueryBuilder('s')
                ->where('s.id_produit = :produitId')
                ->setParameter('produitId', $id_produit)
                ->getQuery()
                ->getResult();
            
            foreach ($stocks as $stock) {
                $em->remove($stock);
            }
            
            // Supprimer le produit
            $em->remove($produit);
            $em->flush();
        }

        return $this->redirectToRoute('produit_index');
    }
}
