<?php

namespace App\Controller;

use App\Repository\CultureRepository;
use App\Repository\ParcelleRepository;
use App\Repository\ProduitRepository;
use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_dashboard')]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/pages/dashboard.html.twig');
    }

    #[Route('/admin/parcelles', name: 'app_admin_parcelles', methods: ['GET'])]
    public function parcelles(Request $request, ParcelleRepository $parcelleRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $q = $request->query->getString('q');
        $sort = $request->query->getString('sort') ?: 'nom';
        $dir = strtoupper($request->query->getString('dir') ?: 'ASC');
        $dir = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'ASC';

        $parcelles = $parcelleRepository->searchByQuery($q, $sort, $dir);
        $countsByEtat = $parcelleRepository->countByEtat($q);
        $totalAll = $parcelleRepository->count([]);

        $viewData = [
            'parcelles' => $parcelles,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
            'stats' => [
                'total_all' => $totalAll,
                'total_filtered' => count($parcelles),
                'counts_by_etat' => $countsByEtat,
            ],
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/pages/parcelles/_results.html.twig', $viewData);
        }

        return $this->render('admin/pages/parcelles.html.twig', $viewData);
    }

    #[Route('/admin/cultures', name: 'app_admin_cultures', methods: ['GET'])]
    public function cultures(Request $request, CultureRepository $cultureRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $q = $request->query->getString('q');
        $sort = $request->query->getString('sort') ?: 'plantation';
        $dir = strtoupper($request->query->getString('dir') ?: 'DESC');
        $dir = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'DESC';

        $cultures = $cultureRepository->searchByQuery($q, $sort, $dir);
        $countsByEtat = $cultureRepository->countByEtatCroissance($q);

        $totalAll = $cultureRepository->count([]);

        $viewData = [
            'cultures' => $cultures,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
            'stats' => [
                'total_all' => $totalAll,
                'total_filtered' => count($cultures),
                'counts_by_etat' => $countsByEtat,
            ],
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/pages/cultures/_results.html.twig', $viewData);
        }

        return $this->render('admin/pages/cultures.html.twig', $viewData);
    }

    #[Route('/admin/produits', name: 'app_admin_produits', methods: ['GET'])]
    public function produits(Request $request, ProduitRepository $produitRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $q = $request->query->getString('q');
        $sort = $request->query->getString('sort') ?: 'nom';
        $dir = strtoupper($request->query->getString('dir') ?: 'ASC');
        $dir = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'ASC';

        $produits = $produitRepository->findBySearchAndSort($q, 'nom', $sort, $dir);
        $countsByType = $produitRepository->countByType($q);
        $totalAll = $produitRepository->count([]);

        $viewData = [
            'produits' => $produits,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
            'stats' => [
                'total_all' => $totalAll,
                'total_filtered' => count($produits),
                'counts_by_type' => $countsByType,
            ],
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/pages/produits/_results.html.twig', $viewData);
        }

        return $this->render('admin/pages/produits.html.twig', $viewData);
    }

    #[Route('/admin/stocks', name: 'app_admin_stocks', methods: ['GET'])]
    public function stocks(Request $request, StockRepository $stockRepository): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STOCK')) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $q = $request->query->getString('q');
        $sort = $request->query->getString('sort') ?: 'dateEntreeDesc';
        $dir = strtoupper($request->query->getString('dir') ?: 'DESC');
        $dir = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'DESC';

        $stocks = $stockRepository->findBySearchAndSort($q, 'idProduit', $sort, $dir);
        $countsByStatus = $stockRepository->countByStatus($q);
        $totalAll = $stockRepository->count([]);

        $viewData = [
            'stocks' => $stocks,
            'q' => $q,
            'sort' => $sort,
            'dir' => $dir,
            'stats' => [
                'total_all' => $totalAll,
                'total_filtered' => count($stocks),
                'counts_by_status' => $countsByStatus,
            ],
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/pages/stocks/_results.html.twig', $viewData);
        }

        return $this->render('admin/pages/stocks.html.twig', $viewData);
    }
}

