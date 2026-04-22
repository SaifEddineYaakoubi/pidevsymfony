<?php

namespace App\Service;

use App\Entity\Client;
use App\Enum\ClientBadge;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service pour gérer la logique métier des badges clients
 * 
 * Ce service calcule et attribue des badges aux clients
 * en fonction du nombre de ventes effectuées.
 */
class ClientBadgeService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ClientRepository $clientRepository,
        private ?LoggerInterface $logger = null
    ) {
    }

    /**
     * Calcule le badge d'un client basé sur son nombre de ventes
     */
    public function calculateBadge(Client $client): ClientBadge
    {
        $venteCount = $client->getVentes()->count();
        return ClientBadge::fromVenteCount($venteCount);
    }

    /**
     * Met à jour le badge d'un client et le persiste en base
     */
    public function updateClientBadge(Client $client, bool $flush = true): ClientBadge
    {
        $badge = $this->calculateBadge($client);
        $client->setBadge($badge->value);

        if ($flush) {
            $this->entityManager->flush();
        }

        $this->logger?->info('Badge mis à jour pour le client', [
            'client_id' => $client->getId_client(),
            'client_nom' => $client->getNom(),
            'badge' => $badge->value,
            'vente_count' => $client->getVentes()->count(),
        ]);

        return $badge;
    }

    /**
     * Met à jour les badges de tous les clients
     * Utile pour une migration ou un recalcul global
     */
    public function updateAllClientBadges(): int
    {
        $clients = $this->clientRepository->findAll();
        $updatedCount = 0;

        foreach ($clients as $client) {
            $this->updateClientBadge($client, false);
            $updatedCount++;
        }

        $this->entityManager->flush();

        $this->logger?->info('Badges mis à jour pour tous les clients', [
            'total_clients' => $updatedCount,
        ]);

        return $updatedCount;
    }

    /**
     * Récupère les statistiques des badges
     */
    public function getBadgeStatistics(): array
    {
        $clients = $this->clientRepository->findAll();
        
        $stats = [
            'gold' => 0,
            'silver' => 0,
            'bronze' => 0,
            'none' => 0,
            'total' => count($clients),
        ];

        foreach ($clients as $client) {
            $badge = $this->calculateBadge($client);
            $stats[$badge->value]++;
        }

        return $stats;
    }

    /**
     * Récupère les clients par badge
     */
    public function getClientsByBadge(ClientBadge $badge): array
    {
        $clients = $this->clientRepository->findAll();
        
        return array_filter($clients, function(Client $client) use ($badge) {
            return $this->calculateBadge($client) === $badge;
        });
    }

    /**
     * Récupère les meilleurs clients (Gold badge)
     */
    public function getTopClients(): array
    {
        return $this->getClientsByBadge(ClientBadge::GOLD);
    }
}
