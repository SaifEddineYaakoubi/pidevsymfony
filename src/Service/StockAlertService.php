<?php

namespace App\Service;

use App\Repository\StockRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service pour vérifier les stocks et envoyer des alertes
 */
class StockAlertService
{
    private StockRepository $stockRepository;
    private MailService $mailService;
    private LoggerInterface $logger;
    private int $seuil;

    public function __construct(
        StockRepository $stockRepository,
        MailService $mailService,
        LoggerInterface $logger,
        #[Autowire(env: 'STOCK_SEUIL')]
        int $seuil
    ) {
        $this->stockRepository = $stockRepository;
        $this->mailService = $mailService;
        $this->logger = $logger;
        $this->seuil = $seuil;
    }

    /**
     * Vérifie tous les stocks et envoie des alertes pour ceux en dessous du seuil
     *
     * @return array Retourne un tableau avec le nombre d'alertes, produits et erreurs
     */
    public function checkAndSendAlerts(): array
    {
        $stocks = $this->stockRepository->findAll();
        $alertesEnvoyees = 0;
        $produitsAlertes = [];
        $erreurs = [];

        foreach ($stocks as $stock) {
            // Vérification du stock selon le getter exact de l'entité Stock
            if ($stock->getQuantite() <= $this->seuil) {
                $produitNom = $stock->getIdProduit()->getNom();
                $stockActuel = (int) $stock->getQuantite();

                // Tentative d'envoi de l'alerte
                if ($this->mailService->sendStockAlert($produitNom, $stockActuel, $this->seuil)) {
                    $alertesEnvoyees++;
                    $produitsAlertes[] = $produitNom;

                    $this->logger->info("Alerte envoyée pour le produit: $produitNom (stock: $stockActuel <= seuil: $this->seuil)");
                } else {
                    $erreur = "Échec de l'envoi de l'alerte pour le produit: $produitNom";
                    $erreurs[] = $erreur;
                    $this->logger->error($erreur);
                }
            }
        }

        return [
            'alertes' => $alertesEnvoyees,
            'produits' => $produitsAlertes,
            'erreurs' => $erreurs
        ];
    }
}