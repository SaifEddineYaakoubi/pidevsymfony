<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Form\StockType;
use App\Repository\StockRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[Route('/stock/crud')]
class StockCrudController extends AbstractController
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    #[Route('/', name: 'stock_index', methods: ['GET'])]
    public function index(Request $request, StockRepository $stockRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $searchField = $request->query->get('searchField', 'idProduit');
        $sort = $request->query->get('sort', 'dateEntreeDesc');

        $stocks = $stockRepository->findBySearchAndSort($search, $searchField, $sort);

        $sortOptions = [
            'dateEntreeAsc' => 'Date entrée - premier',
            'dateEntreeDesc' => 'Date entrée - dernier',
            'dureeVieAsc' => 'Durée de vie - plus courte',
            'dureeVieDesc' => 'Durée de vie - plus longue',
            'quantiteAsc' => 'Quantité - ascendant',
            'quantiteDesc' => 'Quantité - descendant',
            'idProduitAsc' => 'Produit A-Z',
            'idProduitDesc' => 'Produit Z-A',
        ];

        $searchOptions = [
            'idProduit' => 'Produit',
            'quantite' => 'Quantité',
            'dateEntree' => 'Date entrée',
            'dateExpiration' => 'Date expiration',
            'idUser' => 'Utilisateur',
        ];

        return $this->render('stock/index.html.twig', [
            'stocks' => $stocks,
            'search' => $search,
            'searchField' => $searchField,
            'sort' => $sort,
            'sortOptions' => $sortOptions,
            'searchOptions' => $searchOptions,
        ]);
    }

    #[Route('/new', name: 'stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour ajouter du stock.');
        }

        $stock = new Stock();
        $stock->setUtilisateur($user); // Définir automatiquement l'utilisateur connecté

        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier si un stock existe déjà avec les mêmes caractéristiques
            $existingStock = $em->getRepository(Stock::class)->createQueryBuilder('s')
                ->where('s.id_produit = :produit')
                ->andWhere('s.date_entree = :dateEntree')
                ->andWhere('s.date_expiration = :dateExpiration')
                ->andWhere('s.id_user = :user')
                ->setParameter('produit', $stock->getIdProduit())
                ->setParameter('dateEntree', $stock->getDateEntree())
                ->setParameter('dateExpiration', $stock->getDateExpiration())
                ->setParameter('user', $stock->getUtilisateur())
                ->getQuery()
                ->getOneOrNullResult();

            if ($existingStock) {
                // Augmenter la quantité du stock existant
                $existingStock->setQuantite($existingStock->getQuantite() + $stock->getQuantite());
                $em->flush();

                $this->addFlash('success', 'Quantité ajoutée au stock existant.');
            } else {
                // Créer un nouveau stock
                $em->persist($stock);
                $em->flush();

                $this->addFlash('success', 'Nouveau stock ajouté.');
            }

            return $this->redirectToRoute('stock_index');
        }

        return $this->render('stock/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/export-pdf', name: 'stock_export_pdf', methods: ['GET'])]
    public function exportPdf(StockRepository $stockRepository): Response
    {
        $stocks = $stockRepository->findAll();

        $html = $this->twig->render('stock/export-pdf.html.twig', [
            'stocks' => $stocks,
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
                'Content-Disposition' => 'attachment; filename="stock_' . date('Y-m-d_H-i-s') . '.pdf"',
            ]
        );
    }

    #[Route('/{id_stock}', name: 'stock_show', methods: ['GET'])]
    public function show(int $id_stock, StockRepository $repo): Response
    {
        $stock = $repo->find($id_stock);
        if (!$stock) {
            throw $this->createNotFoundException('Stock not found');
        }
        return $this->render('stock/show.html.twig', [
            'stock' => $stock,
        ]);
    }

    #[Route('/{id_stock}/edit', name: 'stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id_stock, StockRepository $repo, EntityManagerInterface $em): Response
    {
        $stock = $repo->find($id_stock);
        if (!$stock) {
            throw $this->createNotFoundException('Stock not found');
        }

        // Sauvegarde l'utilisateur avant que le formulaire ne le réinitialise
        $utilisateur = $stock->getUtilisateur();

        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Restaure l'utilisateur (non présent dans le formulaire)
            if ($stock->getUtilisateur() === null) {
                $stock->setUtilisateur($utilisateur);
            }
            $em->flush();
            $this->addFlash('success', 'Stock modifié avec succès.');
            return $this->redirectToRoute('stock_index');
        }

        return $this->render('stock/edit.html.twig', [
            'form' => $form->createView(),
            'stock' => $stock,
        ]);
    }

    #[Route('/{id_stock}/delete', name: 'stock_delete', methods: ['POST'])]
    public function delete(Request $request, int $id_stock, StockRepository $repo, EntityManagerInterface $em): Response
    {
        $stock = $repo->find($id_stock);
        if (!$stock) {
            throw $this->createNotFoundException('Stock not found');
        }
        if ($this->isCsrfTokenValid('delete' . $stock->getId_stock(), $request->request->get('_token'))) {
            $em->remove($stock);
            $em->flush();
        }

        return $this->redirectToRoute('stock_index');
    }
}
