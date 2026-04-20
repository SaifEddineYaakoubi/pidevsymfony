<?php

namespace App\Controller;

use App\Service\StockAlertService;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;          
use Symfony\Component\Mime\Email;                      
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class StockController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private StockAlertService $stockAlertService;

    public function __construct(
        EntityManagerInterface $entityManager,
        StockAlertService $stockAlertService
    ) {
        $this->entityManager = $entityManager;
        $this->stockAlertService = $stockAlertService;
    }
    #[Route('/stock', name: 'app_stock_home')]
    public function home(): Response
    {
        $produitRepository = $this->entityManager->getRepository(\App\Entity\Produit::class);
        $stockRepository = $this->entityManager->getRepository(\App\Entity\Stock::class);

        $totalProduits = $produitRepository->count([]);
        $totalStocks = $stockRepository->count([]);

        $totalQuantity = $stockRepository->createQueryBuilder('s')
            ->select('SUM(s.quantite) as total')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $averagePrice = $produitRepository->createQueryBuilder('p')
            ->select('AVG(p.prix_unitaire) as avg')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $productTypes = $produitRepository->createQueryBuilder('p')
            ->select('p.type, COUNT(p.id_produit) as count')
            ->groupBy('p.type')
            ->getQuery()
            ->getResult();

        return $this->render('stock/pages/home.html.twig', [
            'stats' => [
                'totalProduits' => $totalProduits,
                'totalStocks' => $totalStocks,
                'totalQuantity' => $totalQuantity,
                'averagePrice' => round($averagePrice, 2),
            ],
            'productTypes' => $productTypes,
        ]);
    }

    #[Route('/stock/about', name: 'app_stock_about')]
    public function about(): Response
    {
        return $this->render('stock/pages/about.html.twig');
    }

    #[Route('/stock/services', name: 'app_stock_services')]
    public function services(): Response
    {
        return $this->render('stock/pages/services.html.twig');
    }

    #[Route('/stock/testimonials', name: 'app_stock_testimonials')]
    public function testimonials(): Response
    {
        return $this->render('stock/pages/testimonials.html.twig');
    }

    #[Route('/stock/blog', name: 'app_stock_blog')]
    public function blog(): Response
    {
        return $this->render('stock/pages/blog.html.twig');
    }

    #[Route('/stock/blog/{slug}', name: 'app_stock_blog_details')]
    public function blogDetails(string $slug): Response
    {
        return $this->render('stock/pages/blog_details.html.twig', [
            'slug' => $slug,
        ]);
    }

    #[Route('/stock/contact', name: 'app_stock_contact')]
    public function contact(): Response
    {
        return $this->render('stock/pages/contact.html.twig');
    }

    #[Route('/stock/statistics', name: 'app_stock_statistics', methods: ['GET'])]
    public function getStatistics(): JsonResponse
    {
        try {
            $stockRepository = $this->entityManager->getRepository(\App\Entity\Stock::class);

            // Compter le nombre total de stocks
            $totalStocks = $stockRepository->count([]);

            // Compter les stocks faibles (inférieurs ou égaux au seuil)
            $seuil = $_ENV['STOCK_SEUIL'] ?? 5;
            $lowStocksQuery = $stockRepository->createQueryBuilder('s')
                ->select('COUNT(s.id_stock)')
                ->where('s.quantite <= :seuil')
                ->setParameter('seuil', $seuil)
                ->getQuery();

            $lowStocks = $lowStocksQuery->getSingleScalarResult();

            return new JsonResponse([
                'status' => 'ok',
                'totalStocks' => $totalStocks,
                'lowStocks' => $lowStocks,
                'seuil' => $seuil
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur lors de la récupération des statistiques: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/stock/test-mail-simple', name: 'app_stock_test_mail_simple')]
    #[IsGranted('PUBLIC_ACCESS')]
    public function testMailSimple(MailService $mailService): Response
    {
        try {
            $result = $mailService->sendStockAlert('TEST PRODUIT', 1, 5);

            if ($result) {
                $this->addFlash('success', '✅ Email de test envoyé avec succès !');
            } else {
                $this->addFlash('error', '❌ Échec de l\'envoi de l\'email de test');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', '❌ Erreur lors du test : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_stock_alert_interface');
    }

    #[Route('/stock/alert-interface', name: 'app_stock_alert_interface')]
    public function alertInterface(): Response
    {
        return $this->render('stock/alert_interface.html.twig');
    }

    #[Route('/stock/send-alerts', name: 'app_stock_send_alerts', methods: ['POST'])]
    public function sendAlerts(Request $request): JsonResponse
    {
        // Vérification que la requête contient le header AJAX ou vient du formulaire
        $isAjax = $request->isXmlHttpRequest() ||
                 $request->headers->get('Content-Type') === 'application/json' ||
                 $request->request->get('ajax') === 'true';

        if (!$isAjax && !$request->headers->has('X-Requested-With')) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Requête non autorisée'
            ], 403);
        }

        try {
            $result = $this->stockAlertService->checkAndSendAlerts();

            if ($result['alertes'] > 0) {
                $response = [
                    'status' => 'ok',
                    'message' => $result['alertes'] . ' email(s) envoyé(s) à maram.abdeladhim@esprit.tn',
                    'alertes' => $result['alertes'],
                    'produits' => $result['produits']
                ];

                // Ajouter les erreurs s'il y en a
                if (!empty($result['erreurs'])) {
                    $response['message'] .= ' (avec ' . count($result['erreurs']) . ' erreur(s))';
                    $response['erreurs'] = $result['erreurs'];
                }

                return new JsonResponse($response);
            } else {
                return new JsonResponse([
                    'status' => 'ok',
                    'message' => '✅ Tous les stocks sont suffisants',
                    'alertes' => 0
                ]);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Erreur : ' . $e->getMessage(),
                'details' => [
                    'type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]
            ], 500);
        }
    }
}

