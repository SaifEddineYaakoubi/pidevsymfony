<?php

namespace App\Controller;

use App\Entity\Vente;
use App\Entity\Utilisateur;
use App\Entity\Stock;
use App\Entity\Produit;
use App\Entity\Client;
use App\Form\VenteType;
use App\Repository\VenteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

class VenteController extends AbstractController
{
    // ==========================================
    // ============ BACK OFFICE (ADMIN) =========
    // ==========================================

    #[Route('/admin/vente', name: 'app_admin_vente_index')]
    public function adminIndex(Request $request, VenteRepository $venteRepository, \App\Service\StatisticsService $statisticsService): Response
    {
        $search = $request->query->get('search');
        $sortBy = (string) $request->query->get('sortBy', 'date_vente');
        $order = (string) $request->query->get('order', 'DESC');

        $ventes = $venteRepository->findBySearchAndSort((string) $search, $sortBy, $order);
        $stats = $venteRepository->getVenteStats();

        // Données pour le modal statistiques
        $ventesParMois  = $statisticsService->getVentesParMois();
        $topProduits    = $statisticsService->getTopProduits(5);
        $topClients     = $statisticsService->getTopClients(5);
        $comparaison    = $statisticsService->getComparaisonMensuelle();
        $statsGlobales  = $statisticsService->getStatistiquesGlobales();
        $prediction     = $statisticsService->getPredictionRevenusMoisProchain();
        $tauxCroissance = $statisticsService->getTauxCroissance();

        $moisLabels = [];
        $moisVentes = [];
        $moisRevenus = [];
        foreach ($ventesParMois as $data) {
            $moisLabels[]  = date('M Y', (int) strtotime($data['mois'] . '-01'));
            $moisVentes[]  = $data['nombre_ventes'];
            $moisRevenus[] = round($data['total_revenus'], 2);
        }

        return $this->render('admin/vente/index.html.twig', [
            'ventes'         => $ventes,
            'stats'          => $stats,
            'search'         => $search ?? '',
            'sortBy'         => $sortBy ?: 'date_vente',
            'order'          => $order ?: 'DESC',
            'nextOrder'      => ($order === 'ASC') ? 'DESC' : 'ASC',
            // Stats modal
            'stats_globales' => $statsGlobales,
            'mois_labels'    => json_encode($moisLabels),
            'mois_ventes'    => json_encode($moisVentes),
            'mois_revenus'   => json_encode($moisRevenus),
            'top_produits'   => $topProduits,
            'top_clients'    => $topClients,
            'comparaison'    => $comparaison,
            'prediction'     => $prediction,
            'taux_croissance'=> $tauxCroissance,
        ]);
    }

    #[Route('/admin/vente/new', name: 'app_admin_vente_new', methods: ['GET', 'POST'])]
    public function adminNew(
        Request $request, 
        EntityManagerInterface $entityManager,
        \App\Service\ClientBadgeService $badgeService,
        \App\Service\PricingService $pricingService
    ): Response
    {
        $vente = new Vente();
        $vente->setIdUser($this->getUser());
        $vente->setDateVente(new \DateTime());

        $form = $this->createForm(VenteType::class, $vente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $produit = $vente->getIdProduit();
            $quantite = $vente->getQuantite();
            $client = $vente->getIdClient();

            if ($produit && $quantite !== null && $client) {
                // Calculer le prix avec réduction basée sur le badge
                $pricing = $pricingService->calculateProductPrice($produit, $quantite, $client);
                
                // Appliquer le prix avec réduction
                $vente->setMontantTotalWithDiscount($pricing['final_price']);

                $entityManager->persist($vente);
                $entityManager->flush();

                // Mettre à jour le badge du client après la vente
                $badge = $badgeService->updateClientBadge($client);
                
                // Messages de succès avec détails de la réduction
                $this->addFlash('success', 'Vente créée avec succès.');
                
                if ($pricing['discount_percentage'] > 0) {
                    $this->addFlash('info', sprintf(
                        '🎉 Réduction %s appliquée: -%s%% (Économie: %s TND)',
                        $badge->getIcon() . ' ' . $badge->getLabel(),
                        $pricing['discount_percentage'],
                        number_format($pricing['discount_amount'], 2)
                    ));
                }

                return $this->redirectToRoute('app_admin_vente_index');
            } else {
                $this->addFlash('error', "Produit, quantité ou client non sélectionné.");
            }
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('admin/vente/new.html.twig', [
            'vente' => $vente,
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/vente/{idVente}/edit', name: 'app_admin_vente_edit', methods: ['GET', 'POST'])]
    public function adminEdit(Request $request, int $idVente, EntityManagerInterface $entityManager): Response
    {
        // 1. On récupère la vente existante (M-housh NEW!)
        $vente = $entityManager->getRepository(Vente::class)->find($idVente);
        if (!$vente) throw $this->createNotFoundException('Vente non trouvée.');

        $form = $this->createForm(VenteType::class, $vente);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $prix = $vente->getIdProduit()->getPrixUnitaire();
                $vente->setMontantTotal($prix * $vente->getQuantite());

                $entityManager->flush();
                $this->addFlash('success', "Vente modifiée avec succès.");
                return $this->redirectToRoute('app_admin_vente_index');
            }
            return $this->render('admin/vente/edit.html.twig', [
                'vente' => $vente,
                'form' => $form->createView()
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $this->render('admin/vente/edit.html.twig', [
            'vente' => $vente,
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/vente/{idVente}/delete', name: 'app_admin_vente_delete', methods: ['POST'])]
    public function adminDelete(Request $request, int $idVente, EntityManagerInterface $entityManager): Response
    {
        $vente = $entityManager->getRepository(Vente::class)->find($idVente);
        if (!$vente) throw $this->createNotFoundException('Vente non trouvée.');

        if ($this->isCsrfTokenValid('delete' . $vente->getIdVente(), (string) $request->request->get('_token'))) {
            $entityManager->remove($vente);
            $entityManager->flush();
            $this->addFlash('success', 'Vente supprimée.');
        }

        return $this->redirectToRoute('app_admin_vente_index');
    }

    // ==========================================
    // ============ FRONT OFFICE ================
    // ==========================================

    #[Route('/vente', name: 'app_vente_index', methods: ['GET'])]
    public function index(Request $request, VenteRepository $venteRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) throw $this->createAccessDeniedException();

        $search = (string) $request->query->get('search', '');
        $sortBy = (string) $request->query->get('sortBy', 'date_vente');
        $order = (string) $request->query->get('order', 'DESC');

        $ventes = $venteRepository->findBySearchAndSortForUser($user, $search, $sortBy, $order);

        return $this->render('vente/index.html.twig', [
            'ventes' => $ventes,
            'search' => $search,
            'stats' => $venteRepository->getVenteStatsForUser($user),
        ]);
    }

    #[Route('/vente/geolocalisation', name: 'app_vente_geolocalisation', methods: ['GET'])]
    public function geolocalisation(VenteRepository $venteRepository): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) throw $this->createAccessDeniedException();

        // Récupérer toutes les ventes de l'utilisateur avec géolocalisation
        $ventes = $venteRepository->findBySearchAndSortForUser($user, '', 'date_vente', 'DESC');
        
        // Filtrer uniquement les ventes avec géolocalisation
        $ventesAvecGeo = array_filter($ventes, function($vente) {
            return $vente->getVille() !== null || $vente->getRegion() !== null;
        });

        // Calculer les statistiques
        $totalVentes = count($ventesAvecGeo);
        $tunisCount = 0;
        $autresCount = 0;
        $totalFrais = 0;

        foreach ($ventesAvecGeo as $vente) {
            $region = $vente->getRegion();
            $frais = $vente->getFraisLivraison() ?? 0;
            $totalFrais += $frais;

            if ($region && stripos($region, 'Tunis') !== false) {
                $tunisCount++;
            } else {
                $autresCount++;
            }
        }

        $stats = [
            'total_ventes' => $totalVentes,
            'tunis_count' => $tunisCount,
            'autres_count' => $autresCount,
            'tunis_percentage' => $totalVentes > 0 ? round(($tunisCount / $totalVentes) * 100, 1) : 0,
            'autres_percentage' => $totalVentes > 0 ? round(($autresCount / $totalVentes) * 100, 1) : 0,
            'total_frais' => $totalFrais
        ];

        return $this->render('vente/geolocalisation.html.twig', [
            'ventes_avec_geo' => $ventesAvecGeo,
            'stats' => $stats
        ]);
    }

    #[Route('/vente/analyse-devises', name: 'app_vente_analyse_devises', methods: ['GET'])]
    public function analyseDevises(
        VenteRepository $venteRepository,
        \App\Service\CurrencyService $currencyService
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) throw $this->createAccessDeniedException();

        // Récupérer toutes les ventes de l'utilisateur
        $ventes = $venteRepository->findBySearchAndSortForUser($user, '', 'date_vente', 'DESC');

        // Récupérer les taux de change actuels
        $rates = $currencyService->getAllRates();

        // Préparer les données avec conversions
        $ventesAvecConversions = [];
        $totalTND = 0;
        $totalEUR = 0;
        $totalUSD = 0;

        foreach ($ventes as $vente) {
            $montantTND = $vente->getMontantTotal();
            $totalTND += $montantTND;

            if ($rates['success']) {
                $montantEUR = $montantTND * $rates['taux']['EUR'];
                $montantUSD = $montantTND * $rates['taux']['USD'];
                $totalEUR += $montantEUR;
                $totalUSD += $montantUSD;
            } else {
                $montantEUR = null;
                $montantUSD = null;
            }

            $ventesAvecConversions[] = [
                'vente' => $vente,
                'montant_tnd' => $montantTND,
                'montant_eur' => $montantEUR,
                'montant_usd' => $montantUSD
            ];
        }

        return $this->render('vente/analyse_devises.html.twig', [
            'ventes_conversions' => $ventesAvecConversions,
            'rates' => $rates,
            'totaux' => [
                'tnd' => $totalTND,
                'eur' => $totalEUR,
                'usd' => $totalUSD
            ]
        ]);
    }

    #[Route('/vente/new', name: 'app_vente_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        \App\Service\ClientBadgeService $badgeService,
        \App\Service\PricingService $pricingService,
        \App\Service\GeoLocationService $geoLocationService
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) throw $this->createAccessDeniedException();

        $vente = new Vente();
        $vente->setIdUser($user);
        $vente->setDateVente(new \DateTime());

        $form = $this->createForm(VenteType::class, $vente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $produit = $vente->getIdProduit();
            $quantite = $vente->getQuantite();
            $client = $vente->getIdClient();

            if ($produit && $quantite !== null && $client) {
                // Récupérer l'IP du visiteur
                $clientIp = $request->getClientIp() ?? '127.0.0.1';
                
                // Obtenir la localisation et les frais de livraison
                $locationData = $geoLocationService->getLocationWithShipping($clientIp);
                
                // Définir la ville, région et frais de livraison
                $vente->setVille($locationData['city']);
                $vente->setRegion($locationData['region']);
                $vente->setFraisLivraison($locationData['frais_livraison']);
                
                // Log pour debug
                if ($locationData['success']) {
                    $this->addFlash('info', sprintf(
                        '📍 Livraison vers %s, %s - Frais: %s DT',
                        $locationData['city'] ?? 'N/A',
                        $locationData['region'] ?? 'N/A',
                        $locationData['frais_livraison']
                    ));
                } else {
                    $this->addFlash('warning', sprintf(
                        '⚠️ Géolocalisation indisponible. Frais par défaut: %s DT',
                        $locationData['frais_livraison']
                    ));
                }
                
                // Calculer le prix avec réduction basée sur le badge
                $pricing = $pricingService->calculateProductPrice($produit, $quantite, $client);
                
                // Appliquer le prix avec réduction (utiliser la méthode spéciale)
                $vente->setMontantTotalWithDiscount($pricing['final_price']);

                $entityManager->persist($vente);
                $entityManager->flush();

                // Mettre à jour le badge du client après la vente
                $badge = $badgeService->updateClientBadge($client);
                
                // Messages de succès avec détails de la réduction
                $this->addFlash('success', 'Vente créée avec succès.');
                
                if ($pricing['discount_percentage'] > 0) {
                    $this->addFlash('info', sprintf(
                        '🎉 Réduction %s appliquée: -%s%% (Économie: %s TND)',
                        $badge->getIcon() . ' ' . $badge->getLabel(),
                        $pricing['discount_percentage'],
                        number_format($pricing['discount_amount'], 2)
                    ));
                }
                
                $this->addFlash('info', sprintf(
                    'Badge du client mis à jour: %s %s',
                    $badge->getIcon(),
                    $badge->getLabel()
                ));

                return $this->redirectToRoute('app_vente_index');
            } else {
                $this->addFlash('error', "Produit, quantité ou client non sélectionné.");
            }
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('vente/new.html.twig', [
            'vente' => $vente,
            'form' => $form->createView()
        ]);
    }

    #[Route('/vente/export/pdf', name: 'app_vente_export_pdf', methods: ['GET'])]
    public function exportPdf(VenteRepository $venteRepository): Response
    {
        $ventes = $venteRepository->findAll();

        $html = $this->renderView('admin/vente/pdf.html.twig', [
            'ventes' => $ventes,
            'exportDate' => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="liste_ventes.pdf"'
        ]);
    }

    #[Route('/vente/{idVente}', name: 'app_vente_show', methods: ['GET'])]
    public function show(
        int $idVente, 
        EntityManagerInterface $entityManager,
        \App\Service\CurrencyService $currencyService
    ): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) throw $this->createAccessDeniedException();

        $vente = $entityManager->getRepository(Vente::class)->find($idVente);
        if (!$vente) {
            throw $this->createNotFoundException('Vente non trouvée.');
        }

        // Vérifier que la vente appartient à l'utilisateur connecté
        if ($vente->getIdUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        // Convertir le montant en EUR
        $montantTND = $vente->getMontantTotal();
        $conversionResult = $currencyService->convertTNDtoEUR($montantTND);

        return $this->render('vente/show.html.twig', [
            'vente' => $vente,
            'conversion' => $conversionResult,
        ]);
    }

    #[Route('/vente/{idVente}/edit', name: 'app_vente_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $idVente, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) throw $this->createAccessDeniedException();

        $vente = $entityManager->getRepository(Vente::class)->find($idVente);
        if (!$vente) {
            throw $this->createNotFoundException('Vente non trouvée.');
        }

        // Vérifier que la vente appartient à l'utilisateur connecté
        if ($vente->getIdUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(VenteType::class, $vente);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $produit = $vente->getIdProduit();
                $quantite = $vente->getQuantite();

                if ($produit && $quantite !== null) {
                    $prix = $produit->getPrixUnitaire();
                    $vente->setMontantTotal($prix * $quantite);
                }

                $entityManager->flush();
                $this->addFlash('success', "Vente modifiée avec succès.");
                return $this->redirectToRoute('app_vente_index');
            }
            return $this->render('vente/edit.html.twig', [
                'vente' => $vente,
                'form' => $form->createView()
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        return $this->render('vente/edit.html.twig', [
            'vente' => $vente,
            'form' => $form->createView()
        ]);
    }

    // ==========================================
    // ============ ACTIONS COMMUNES ============
    // ==========================================


    #[Route('/vente/{idVente}/delete', name: 'app_vente_delete', methods: ['POST'])]
    public function delete(Request $request, int $idVente, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $vente = $entityManager->getRepository(Vente::class)->find($idVente);

        if (!$vente) {
            $this->addFlash('error', 'Vente non trouvée.');
            return $this->redirectToRoute('app_vente_index');
        }

        // Verify CSRF token
        if (!$this->isCsrfTokenValid('delete' . $vente->getIdVente(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_vente_index');
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        if (!$isAdmin && $vente->getIdUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        // Delete vente and restore stock
        $produit = $vente->getIdProduit();
        if ($produit) {
            $stock = $entityManager->getRepository(Stock::class)->findOneBy(['id_produit' => $produit]);
            if ($stock) {
                $stock->setQuantite($stock->getQuantite() + $vente->getQuantite());
            }
        }

        $entityManager->remove($vente);
        $entityManager->flush();
        $this->addFlash('success', 'Vente supprimée.');

        // Redirect based on user role
        return $this->redirectToRoute($isAdmin ? 'app_admin_vente_index' : 'app_vente_index');
    }

    private function processVente(Vente $vente, $form, Request $request, EntityManagerInterface $entityManager): bool
    {
        if (!$form->isValid()) return false;

        $produit = $vente->getIdProduit();
        $quantiteVendue = $vente->getQuantite();
        $vente->setMontantTotal($produit->getPrixUnitaire() * $quantiteVendue);

        $stock = $entityManager->getRepository(Stock::class)->findOneBy(['id_produit' => $produit]);
        if (!$stock || $stock->getQuantite() < $quantiteVendue) {
            $this->addFlash('error', "Stock insuffisant !");
            return false;
        }

        $stock->setQuantite($stock->getQuantite() - $quantiteVendue);
        $entityManager->persist($vente);
        $entityManager->flush();
        return true;
    }

    // ==========================================
    // ============ QR CODE ROUTES ==============
    // ==========================================

    /**
     * Page intermédiaire qui déclenche automatiquement le téléchargement du PDF
     */
    #[Route('/vente/{idVente}/download-page', name: 'app_vente_download_page', methods: ['GET'])]
    public function venteDownloadPage(
        int $idVente,
        VenteRepository $venteRepository
    ): Response {
        $vente = $venteRepository->find($idVente);

        if (!$vente) {
            throw $this->createNotFoundException('Vente non trouvée');
        }

        return $this->render('vente/download_page.html.twig', [
            'vente' => $vente,
            'downloadUrl' => $this->generateUrl('app_vente_pdf', ['idVente' => $idVente])
        ]);
    }

    /**
     * Page intermédiaire qui déclenche automatiquement le téléchargement du PDF client
     */
    #[Route('/client/{id_client}/download-page', name: 'app_client_download_page', methods: ['GET'])]
    public function clientDownloadPage(
        int $id_client,
        EntityManagerInterface $entityManager
    ): Response {
        $client = $entityManager->getRepository(Client::class)->find($id_client);

        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé');
        }

        return $this->render('client/download_page.html.twig', [
            'client' => $client,
            'downloadUrl' => $this->generateUrl('app_client_pdf', ['id_client' => $id_client])
        ]);
    }

    /**
     * Génère un PDF pour une vente (accessible via QR code)
     * Le QR code pointe vers cette route pour télécharger automatiquement le PDF
     */
    #[Route('/vente/{idVente}/pdf', name: 'app_vente_pdf', methods: ['GET'])]
    public function generateVentePdf(
        int $idVente,
        VenteRepository $venteRepository
    ): Response {
        $vente = $venteRepository->find($idVente);

        if (!$vente) {
            throw $this->createNotFoundException('Vente non trouvée');
        }

        $html = $this->renderView('vente/pdf_detail.html.twig', [
            'vente' => $vente,
            'generatedDate' => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Forcer le téléchargement immédiat (pas d'affichage dans le navigateur)
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="vente_' . $idVente . '.pdf"',
            'Content-Length' => strlen($dompdf->output()),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    /**
     * Génère un PDF pour un client (accessible via QR code)
     * Le QR code pointe vers cette route pour télécharger automatiquement le PDF
     */
    #[Route('/client/{id_client}/pdf', name: 'app_client_pdf', methods: ['GET'])]
    public function generateClientPdf(
        int $id_client,
        EntityManagerInterface $entityManager
    ): Response {
        $client = $entityManager->getRepository(Client::class)->find($id_client);

        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé');
        }

        $html = $this->renderView('client/pdf_detail.html.twig', [
            'client' => $client,
            'generatedDate' => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Forcer le téléchargement immédiat (pas d'affichage dans le navigateur)
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="client_' . $id_client . '.pdf"',
            'Content-Length' => strlen($dompdf->output()),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }

    /**
     * Génère un QR Code pour une vente (retourne JSON avec base64)
     */
    #[Route('/vente/{idVente}/qrcode', name: 'app_vente_qrcode', methods: ['GET'])]
    public function generateVenteQrCode(
        int $idVente,
        VenteRepository $venteRepository,
        \App\Service\QrCodeService $qrCodeService
    ): Response {
        $vente = $venteRepository->find($idVente);

        if (!$vente) {
            return $this->json(['error' => 'Vente non trouvée'], 404);
        }

        // Générer le QR code en base64
        $qrCodeBase64 = $qrCodeService->generateVenteQrCode($vente, true, true);

        return $this->json([
            'success' => true,
            'qrcode' => $qrCodeBase64,
            'vente_id' => $idVente,
            'label' => 'Vente #' . $idVente
        ]);
    }

    /**
     * Télécharge l'image du QR Code d'une vente (PNG)
     */
    #[Route('/vente/{idVente}/qrcode/download', name: 'app_vente_qrcode_download', methods: ['GET'])]
    public function downloadVenteQrCode(
        int $idVente,
        VenteRepository $venteRepository,
        \App\Service\QrCodeService $qrCodeService
    ): Response {
        $vente = $venteRepository->find($idVente);

        if (!$vente) {
            throw $this->createNotFoundException('Vente non trouvée');
        }

        // Générer l'image du QR code
        $qrCodeBase64 = $qrCodeService->generateVenteQrCode($vente, true, true);
        $downloadData = $qrCodeService->prepareQrCodeForDownload(
            $qrCodeBase64,
            'vente_' . $idVente . '_qrcode.png'
        );

        return new Response(
            $downloadData['content'],
            200,
            [
                'Content-Type' => $downloadData['mimeType'],
                'Content-Disposition' => 'attachment; filename="' . $downloadData['filename'] . '"'
            ]
        );
    }

    /**
     * Génère un QR Code pour un client (retourne JSON avec base64)
     */
    #[Route('/client/{id_client}/qrcode', name: 'app_client_qrcode', methods: ['GET'])]
    public function generateClientQrCode(
        int $id_client,
        EntityManagerInterface $entityManager,
        \App\Service\QrCodeService $qrCodeService
    ): Response {
        $client = $entityManager->getRepository(Client::class)->find($id_client);

        if (!$client) {
            return $this->json(['error' => 'Client non trouvé'], 404);
        }

        // Générer le QR code en base64
        $qrCodeBase64 = $qrCodeService->generateClientQrCode($client, true, true);

        return $this->json([
            'success' => true,
            'qrcode' => $qrCodeBase64,
            'client_id' => $id_client,
            'label' => $client->getNom()
        ]);
    }

    /**
     * Télécharge l'image du QR Code d'un client (PNG)
     */
    #[Route('/client/{id_client}/qrcode/download', name: 'app_client_qrcode_download', methods: ['GET'])]
    public function downloadClientQrCode(
        int $id_client,
        EntityManagerInterface $entityManager,
        \App\Service\QrCodeService $qrCodeService
    ): Response {
        $client = $entityManager->getRepository(Client::class)->find($id_client);

        if (!$client) {
            throw $this->createNotFoundException('Client non trouvé');
        }

        // Générer l'image du QR code
        $qrCodeBase64 = $qrCodeService->generateClientQrCode($client, true, true);
        $downloadData = $qrCodeService->prepareQrCodeForDownload(
            $qrCodeBase64,
            'client_' . $id_client . '_qrcode.png'
        );

        return new Response(
            $downloadData['content'],
            200,
            [
                'Content-Type' => $downloadData['mimeType'],
                'Content-Disposition' => 'attachment; filename="' . $downloadData['filename'] . '"'
            ]
        );
    }

}
