<?php
// src/Service/ClientAnalysisService.php
namespace App\Service;

use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Repository\VenteRepository;
use Doctrine\ORM\EntityManagerInterface;

class ClientAnalysisService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ClientRepository $clientRepository,
        private VenteRepository $venteRepository
    ) {
    }

    /**
     * Analyse complète d'un client
     */
    public function analyzeClient(Client $client): array
    {
        $ventes = $client->getVentes();
        $nombreVentes = count($ventes);
        
        $totalDepense = 0;
        $dernierAchat = null;
        
        foreach ($ventes as $vente) {
            $totalDepense += $vente->getMontantTotal();
            if (!$dernierAchat || $vente->getDateVente() > $dernierAchat) {
                $dernierAchat = $vente->getDateVente();
            }
        }
        
        $panierMoyen = $nombreVentes > 0 ? $totalDepense / $nombreVentes : 0;
        
        // Calcul du score de fidélité (0-100)
        $score = $this->calculateLoyaltyScore($nombreVentes, $totalDepense, $dernierAchat);
        
        // Catégorie du client
        $categorie = $this->getClientCategory($score);
        
        // Jours depuis dernier achat
        $joursDernierAchat = $dernierAchat ? (new \DateTime())->diff($dernierAchat)->days : null;
        
        // Risque de perte
        $risquePerte = $this->calculateChurnRisk($joursDernierAchat, $nombreVentes);
        
        return [
            'nombre_ventes' => $nombreVentes,
            'total_depense' => $totalDepense,
            'panier_moyen' => $panierMoyen,
            'dernier_achat' => $dernierAchat,
            'jours_dernier_achat' => $joursDernierAchat,
            'score_fidelite' => $score,
            'categorie' => $categorie,
            'risque_perte' => $risquePerte,
            'recommandations' => $this->getRecommendations($score, $risquePerte, $joursDernierAchat),
        ];
    }

    /**
     * Calcul du score de fidélité (0-100)
     */
    private function calculateLoyaltyScore(int $nombreVentes, float $totalDepense, ?\DateTimeInterface $dernierAchat): int
    {
        $score = 0;
        
        // Points pour le nombre de ventes (max 40 points)
        $score += min(40, $nombreVentes * 5);
        
        // Points pour le montant total (max 30 points)
        $score += min(30, ($totalDepense / 100) * 2);
        
        // Points pour la récence (max 30 points)
        if ($dernierAchat) {
            $joursDernierAchat = (new \DateTime())->diff($dernierAchat)->days;
            if ($joursDernierAchat <= 30) {
                $score += 30;
            } elseif ($joursDernierAchat <= 60) {
                $score += 20;
            } elseif ($joursDernierAchat <= 90) {
                $score += 10;
            }
        }
        
        return min(100, $score);
    }

    /**
     * Catégorie du client basée sur le score
     */
    private function getClientCategory(int $score): array
    {
        if ($score >= 80) {
            return ['nom' => 'VIP', 'couleur' => 'success', 'icon' => '👑'];
        } elseif ($score >= 60) {
            return ['nom' => 'Fidèle', 'couleur' => 'primary', 'icon' => '⭐'];
        } elseif ($score >= 40) {
            return ['nom' => 'Régulier', 'couleur' => 'info', 'icon' => '👤'];
        } elseif ($score >= 20) {
            return ['nom' => 'Occasionnel', 'couleur' => 'warning', 'icon' => '🔔'];
        } else {
            return ['nom' => 'Nouveau', 'couleur' => 'secondary', 'icon' => '🆕'];
        }
    }

    /**
     * Calcul du risque de perte (0-100)
     */
    private function calculateChurnRisk(?int $joursDernierAchat, int $nombreVentes): array
    {
        if (!$joursDernierAchat) {
            return ['niveau' => 0, 'label' => 'Aucun', 'couleur' => 'secondary'];
        }
        
        $risque = 0;
        
        // Plus le dernier achat est ancien, plus le risque est élevé
        if ($joursDernierAchat > 180) {
            $risque = 90;
        } elseif ($joursDernierAchat > 120) {
            $risque = 70;
        } elseif ($joursDernierAchat > 90) {
            $risque = 50;
        } elseif ($joursDernierAchat > 60) {
            $risque = 30;
        } else {
            $risque = 10;
        }
        
        // Ajustement selon le nombre de ventes
        if ($nombreVentes >= 10) {
            $risque -= 20;
        } elseif ($nombreVentes >= 5) {
            $risque -= 10;
        }
        
        $risque = max(0, min(100, $risque));
        
        if ($risque >= 70) {
            return ['niveau' => $risque, 'label' => 'Élevé', 'couleur' => 'danger'];
        } elseif ($risque >= 40) {
            return ['niveau' => $risque, 'label' => 'Moyen', 'couleur' => 'warning'];
        } else {
            return ['niveau' => $risque, 'label' => 'Faible', 'couleur' => 'success'];
        }
    }

    /**
     * Recommandations personnalisées
     */
    private function getRecommendations(int $score, array $risquePerte, ?int $joursDernierAchat): array
    {
        $recommandations = [];
        
        // Recommandations basées sur le risque de perte
        if ($risquePerte['niveau'] >= 70) {
            $recommandations[] = [
                'type' => 'urgent',
                'icon' => '🚨',
                'message' => 'Client à risque ! Contactez-le rapidement avec une offre spéciale.'
            ];
        } elseif ($risquePerte['niveau'] >= 40) {
            $recommandations[] = [
                'type' => 'attention',
                'icon' => '⚠️',
                'message' => 'Client inactif depuis un moment. Envoyez un email de relance.'
            ];
        }
        
        // Recommandations basées sur le score
        if ($score >= 80) {
            $recommandations[] = [
                'type' => 'vip',
                'icon' => '👑',
                'message' => 'Client VIP ! Offrez-lui des avantages exclusifs et un service premium.'
            ];
        } elseif ($score >= 60) {
            $recommandations[] = [
                'type' => 'fidelite',
                'icon' => '🎁',
                'message' => 'Client fidèle. Proposez-lui un programme de fidélité ou des réductions.'
            ];
        } elseif ($score < 40) {
            $recommandations[] = [
                'type' => 'engagement',
                'icon' => '📧',
                'message' => 'Nouveau client. Envoyez des newsletters pour l\'engager davantage.'
            ];
        }
        
        // Recommandations basées sur la récence
        if ($joursDernierAchat && $joursDernierAchat > 90) {
            $recommandations[] = [
                'type' => 'reactivation',
                'icon' => '🔄',
                'message' => 'Pas d\'achat depuis ' . $joursDernierAchat . ' jours. Campagne de réactivation recommandée.'
            ];
        }
        
        return $recommandations;
    }

    /**
     * Statistiques globales des clients
     */
    public function getGlobalStats(): array
    {
        $clients = $this->clientRepository->findAll();
        
        $stats = [
            'total' => count($clients),
            'vip' => 0,
            'fideles' => 0,
            'reguliers' => 0,
            'occasionnels' => 0,
            'nouveaux' => 0,
            'risque_eleve' => 0,
            'risque_moyen' => 0,
            'risque_faible' => 0,
        ];
        
        foreach ($clients as $client) {
            $analyse = $this->analyzeClient($client);
            $score = $analyse['score_fidelite'];
            $risque = $analyse['risque_perte']['niveau'];
            
            // Catégories
            if ($score >= 80) $stats['vip']++;
            elseif ($score >= 60) $stats['fideles']++;
            elseif ($score >= 40) $stats['reguliers']++;
            elseif ($score >= 20) $stats['occasionnels']++;
            else $stats['nouveaux']++;
            
            // Risques
            if ($risque >= 70) $stats['risque_eleve']++;
            elseif ($risque >= 40) $stats['risque_moyen']++;
            else $stats['risque_faible']++;
        }
        
        return $stats;
    }
}
