<?php

namespace App\Controller;

use App\Entity\Recolte_archive;
use App\Entity\Utilisateur;
use App\Repository\Recolte_archiveRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/archive')]
class RecolteArchiveController extends AbstractController
{
    #[Route('/', name: 'app_archive_index', methods: ['GET'])]
    public function index(Recolte_archiveRepository $archiveRepository, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $q = $request->query->getString('q', '');
        $sort = $request->query->getString('sort', 'archivage_desc');

        $archives = $archiveRepository->findForIndexForUser($user, $q, $sort);
        $stats = $archiveRepository->getIndexStatsForUser($user, $q);

        if ($request->isXmlHttpRequest()) {
            return $this->render('archive/_results.html.twig', [
                'archives' => $archives,
                'stats' => $stats,
                'q' => $q,
                'sort' => $sort,
            ]);
        }

        return $this->render('archive/index.html.twig', [
            'archives' => $archives,
            'stats' => $stats,
            'q' => $q,
            'sort' => $sort,
        ]);
    }

    #[Route('/{idArchive}', name: 'app_archive_show', methods: ['GET'])]
    public function show(int $idArchive, Recolte_archiveRepository $archiveRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $archive = $archiveRepository->findOneForUser($idArchive, $user);

        if (!$archive) {
            throw $this->createNotFoundException('Archive non trouvée');
        }

        return $this->render('archive/show.html.twig', [
            'archive' => $archive,
        ]);
    }
}
