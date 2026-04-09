<?php

namespace App\Controller;

use App\Entity\Recolte_archive;
use App\Repository\Recolte_archiveRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/archive')]
class RecolteArchiveController extends AbstractController
{
    #[Route('/', name: 'app_archive_index', methods: ['GET'])]
    public function index(Recolte_archiveRepository $archiveRepository): Response
    {
        return $this->render('archive/index.html.twig', [
            'archives' => $archiveRepository->findAll(),
        ]);
    }

    #[Route('/{idArchive}', name: 'app_archive_show', methods: ['GET'])]
    public function show(int $idArchive, Recolte_archiveRepository $archiveRepository): Response
    {
        $archive = $archiveRepository->find($idArchive);

        if (!$archive) {
            throw $this->createNotFoundException('Archive non trouvée');
        }

        return $this->render('archive/show.html.twig', [
            'archive' => $archive,
        ]);
    }
}
