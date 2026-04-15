<?php

namespace App\Controller;

use App\Repository\CultureRepository;
use App\Repository\ParcelleRepository;
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
}

