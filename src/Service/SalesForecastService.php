<?php
// src/Service/SalesForecastService.php
namespace App\Service;

use App\Repository\VenteRepository;
use Doctrine\ORM\EntityManagerInterface;

class SalesForecastService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private VenteRepository $venteRepository
    ) {
    }

    /**
     * Prévisions pour les 3 prochains mois
     */
    /** @return array<string, mixed> */
    public function getForecast(): array
    {
        $historique = $this->getHistoricalData(12); // 12 derniers mois
        
        if (count($historique) < 3) {
            return [
                'previsions' => [],
                'tendance' => 'insuffisant',
                'confiance' => 0,
                'historique' => $historique,
            ];
        }
        
        // Calcul de la tendance (régression linéaire simple)
        $tendance = $this->calculateTrend($historique);
        
        // Génération des prévisions
        $previsions = [];
        $dernierMois = count($historique);
        
        for ($i = 1; $i <= 3; $i++) {
            $moisFutur = $dernierMois + $i;
            $prevision = $this->predictValue($moisFutur, $tendance, $historique);
            
            $date = new \DateTime();
            $date->modify("+{$i} month");
            
            $previsions[] = [
                'mois' => $date->format('M Y'),
                'date' => $date,
                'ventes_prevues' => round($prevision['ventes']),
                'revenus_prevus' => round($prevision['revenus'], 2),
                'confiance' => $prevision['confiance'],
            ];
        }
        
        // Calcul de la tendance globale
        $tendanceGlobale = $this->getTrendDirection($tendance);
        
        return [
            'previsions' => $previsions,
            'tendance' => $tendanceGlobale,
            'confiance' => $this->calculateConfidence($historique),
            'historique' => $historique,
        ];
    }

    /**
     * Analyse des produits les plus rentables
     */
    /** @return array<string, mixed> */
    public function getProfitabilityAnalysis(): array
    {
        $sql = "
            SELECT 
                p.nom as produit,
                COUNT(v.id_vente) as nombre_ventes,
                SUM(v.quantite) as quantite_totale,
                SUM(v.montant_total) as revenus_totaux,
                AVG(v.montant_total) as prix_moyen,
                (SUM(v.montant_total) / COUNT(v.id_vente)) as rentabilite_par_vente
            FROM vente v
            INNER JOIN produit p ON v.id_produit = p.id_produit
            GROUP BY p.id_produit, p.nom
            ORDER BY rentabilite_par_vente DESC
            LIMIT 10
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        
        return $result->fetchAllAssociative();
    }

    /**
     * Analyse des périodes de vente
     */
    /** @return array<string, mixed> */
    public function getSeasonalityAnalysis(): array
    {
        $sql = "
            SELECT 
                MONTH(date_vente) as mois,
                COUNT(*) as nombre_ventes,
                SUM(montant_total) as revenus,
                AVG(montant_total) as panier_moyen
            FROM vente
            WHERE date_vente >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY MONTH(date_vente)
            ORDER BY mois
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        $data = $result->fetchAllAssociative();
        
        // Identifier le meilleur et le pire mois
        $meilleurMois = null;
        $pireMois = null;
        $maxRevenus = 0;
        $minRevenus = PHP_FLOAT_MAX;
        
        foreach ($data as $row) {
            if ($row['revenus'] > $maxRevenus) {
                $maxRevenus = $row['revenus'];
                $meilleurMois = $row;
            }
            if ($row['revenus'] < $minRevenus) {
                $minRevenus = $row['revenus'];
                $pireMois = $row;
            }
        }
        
        return [
            'donnees' => $data,
            'meilleur_mois' => $meilleurMois,
            'pire_mois' => $pireMois,
        ];
    }

    /**
     * Recommandations stratégiques
     */
    /** @return array<string, mixed> */
    public function getStrategicRecommendations(): array
    {
        $forecast = $this->getForecast();
        $profitability = $this->getProfitabilityAnalysis();
        $seasonality = $this->getSeasonalityAnalysis();
        
        $recommendations = [];
        
        // Recommandation basée sur la tendance
        if ($forecast['tendance'] === 'hausse') {
            $recommendations[] = [
                'type' => 'opportunite',
                'icon' => '📈',
                'titre' => 'Tendance Positive',
                'message' => 'Vos ventes sont en hausse ! Augmentez vos stocks et lancez des campagnes marketing.',
                'priorite' => 'haute',
            ];
        } elseif ($forecast['tendance'] === 'baisse') {
            $recommendations[] = [
                'type' => 'alerte',
                'icon' => '📉',
                'titre' => 'Tendance Négative',
                'message' => 'Vos ventes diminuent. Analysez les causes et ajustez votre stratégie.',
                'priorite' => 'critique',
            ];
        }
        
        // Recommandation sur les produits
        if (count($profitability) > 0) {
            $topProduit = $profitability[0];
            $recommendations[] = [
                'type' => 'produit',
                'icon' => '🏆',
                'titre' => 'Produit Star',
                'message' => "Le produit '{$topProduit['produit']}' génère le plus de revenus par vente. Mettez-le en avant !",
                'priorite' => 'moyenne',
            ];
        }
        
        // Recommandation saisonnière
        if ($seasonality['meilleur_mois']) {
            $moisNom = $this->getMoisNom($seasonality['meilleur_mois']['mois']);
            $recommendations[] = [
                'type' => 'saisonnalite',
                'icon' => '📅',
                'titre' => 'Saisonnalité',
                'message' => "Le mois de {$moisNom} est votre meilleur mois. Préparez des promotions spéciales.",
                'priorite' => 'moyenne',
            ];
        }
        
        return $recommendations;
    }

    /**
     * Données historiques
     */
    /** @return array<string, mixed> */
    private function getHistoricalData(int $months): array
    {
        $sql = "
            SELECT 
                DATE_FORMAT(date_vente, '%Y-%m') as mois,
                COUNT(*) as nombre_ventes,
                SUM(montant_total) as revenus
            FROM vente
            WHERE date_vente >= DATE_SUB(NOW(), INTERVAL " . $months . " MONTH)
            GROUP BY DATE_FORMAT(date_vente, '%Y-%m')
            ORDER BY mois ASC
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        
        return $result->fetchAllAssociative();
    }

    /**
     * Calcul de la tendance (régression linéaire)
     */
    /** @return array<string, mixed> */
    private function calculateTrend(array $data): array
    {
        $n = count($data);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        
        foreach ($data as $i => $row) {
            $x = $i + 1;
            $y = (float) $row['revenus'];
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        return [
            'slope' => $slope,
            'intercept' => $intercept,
        ];
    }

    /**
     * Prédiction d'une valeur future
     */
    /** @return array<string, mixed> */
    private function predictValue(int $x, array $tendance, array $historique): array
    {
        $revenusPredit = $tendance['slope'] * $x + $tendance['intercept'];
        
        // Calcul de la moyenne des ventes
        $totalVentes = array_sum(array_column($historique, 'nombre_ventes'));
        $moyenneVentes = $totalVentes / count($historique);
        
        // Confiance basée sur la variance
        $confiance = min(95, 70 + (count($historique) * 2));
        
        return [
            'ventes' => $moyenneVentes,
            'revenus' => max(0, $revenusPredit),
            'confiance' => $confiance,
        ];
    }

    /**
     * Direction de la tendance
     */
    private function getTrendDirection(array $tendance): string
    {
        if ($tendance['slope'] > 50) {
            return 'hausse';
        } elseif ($tendance['slope'] < -50) {
            return 'baisse';
        } else {
            return 'stable';
        }
    }

    /**
     * Calcul de la confiance
     */
    private function calculateConfidence(array $historique): int
    {
        $n = count($historique);
        
        if ($n >= 12) return 95;
        if ($n >= 6) return 80;
        if ($n >= 3) return 60;
        
        return 40;
    }

    /**
     * Nom du mois en français
     */
    private function getMoisNom(int $mois): string
    {
        $moisNoms = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];
        
        return $moisNoms[$mois] ?? 'Inconnu';
    }
}
