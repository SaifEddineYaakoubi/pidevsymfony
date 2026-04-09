<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[Route('/produit')]
class ProduitController extends AbstractController
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    #[Route('/', name: 'produit_index', methods: ['GET'])]
    public function index(Request $request, ProduitRepository $produitRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $searchField = $request->query->get('searchField', 'nom');
        $sort = $request->query->get('sort', 'idProduit');
        $direction = strtoupper($request->query->get('direction', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';

        $produits = $produitRepository->findBySearchAndSort($search, $searchField, $sort, $direction);

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
        $produits = $produitRepository->findAll();

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

    #[Route('/{id_produit}', name: 'produit_show', methods: ['GET'])]
    public function show(int $id_produit, ProduitRepository $repo): Response
    {
        $produit = $repo->find($id_produit);
        if (!$produit) {
            throw $this->createNotFoundException('Produit not found');
        }
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
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
        if ($this->isCsrfTokenValid('delete' . $produit->getId_produit(), $request->request->get('_token'))) {
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
