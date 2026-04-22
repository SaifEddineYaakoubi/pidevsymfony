<?php
// src/Service/StatisticsService.php
namespace App\Service;

use App\Repository\VenteRepository;
use App\Repository\ClientRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;

class StatisticsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private VenteRepository $venteRepository,
        private ClientRepository $clientRepository,
        private ProduitRepository $produitRepository
    ) {
    }

    /**
     * Statistiques des ventes par mois (12 derniers mois)
     */
    public function getVentesParMois(): array
    {
        $sql = "
            SELECT 
                DATE_FORMAT(date_vente, '%Y-%m') as mois,
                COUNT(*) as nombre_ventes,
                SUM(montant_total) as total_revenus
            FROM vente
            WHERE date_vente >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(date_vente, '%Y-%m')
            ORDER BY mois ASC
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        
        return $result->fetchAllAssociative();
    }

    /**
     * Top 5 des produits les plus vendus
     */
    public function getTopProduits(int $limit = 5): array
    {
        $sql = "
            SELECT 
                p.nom as produit,
                COUNT(v.id_vente) as nombre_ventes,
                SUM(v.quantite) as quantite_totale,
                SUM(v.montant_total) as revenus_totaux
            FROM vente v
            INNER JOIN produit p ON v.id_produit = p.id_produit
            GROUP BY p.id_produit, p.nom
            ORDER BY revenus_totaux DESC
            LIMIT " . $limit . "
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        
        return $result->fetchAllAssociative();
    }

    /**
     * Top 5 des meilleurs clients
     */
    public function getTopClients(int $limit = 5): array
    {
        $sql = "
            SELECT 
                c.nom as client,
                COUNT(v.id_vente) as nombre_achats,
                SUM(v.montant_total) as total_depense
            FROM vente v
            INNER JOIN client c ON v.id_client = c.id_client
            GROUP BY c.id_client, c.nom
            ORDER BY total_depense DESC
            LIMIT " . $limit . "
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        
        return $result->fetchAllAssociative();
    }

    /**
     * Comparaison mois actuel vs mois précédent
     */
    public function getComparaisonMensuelle(): array
    {
        $sql = "
            SELECT 
                CASE 
                    WHEN MONTH(date_vente) = MONTH(NOW()) THEN 'mois_actuel'
                    ELSE 'mois_precedent'
                END as periode,
                COUNT(*) as nombre_ventes,
                SUM(montant_total) as total_revenus,
                AVG(montant_total) as montant_moyen
            FROM vente
            WHERE date_vente >= DATE_SUB(NOW(), INTERVAL 2 MONTH)
            GROUP BY periode
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        
        $data = [];
        foreach ($result->fetchAllAssociative() as $row) {
            $data[$row['periode']] = $row;
        }
        
        return $data;
    }

    /**
     * Statistiques globales
     */
    public function getStatistiquesGlobales(): array
    {
        $totalVentes = $this->venteRepository->count([]);
        $totalClients = $this->clientRepository->count([]);
        $totalProduits = $this->produitRepository->count([]);

        $sql = "SELECT SUM(montant_total) as total_revenus, AVG(montant_total) as montant_moyen FROM vente";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        $revenus = $result->fetchAssociative();

        return [
            'total_ventes' => $totalVentes,
            'total_clients' => $totalClients,
            'total_produits' => $totalProduits,
            'total_revenus' => $revenus['total_revenus'] ?? 0,
            'montant_moyen' => $revenus['montant_moyen'] ?? 0,
        ];
    }

    /**
     * Prédiction des revenus du mois prochain (basée sur la moyenne des 3 derniers mois)
     */
    public function getPredictionRevenusMoisProchain(): float
    {
        $sql = "
            SELECT AVG(total_mois) as moyenne
            FROM (
                SELECT SUM(montant_total) as total_mois
                FROM vente
                WHERE date_vente >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
                GROUP BY DATE_FORMAT(date_vente, '%Y-%m')
            ) as sous_requete
        ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $result = $stmt->executeQuery();
        $data = $result->fetchAssociative();
        
        return (float) ($data['moyenne'] ?? 0);
    }

    /**
     * Taux de croissance mensuel (%)
     */
    public function getTauxCroissance(): float
    {
        $comparaison = $this->getComparaisonMensuelle();
        
        $moisActuel = $comparaison['mois_actuel']['total_revenus'] ?? 0;
        $moisPrecedent = $comparaison['mois_precedent']['total_revenus'] ?? 0;
        
        if ($moisPrecedent == 0) {
            return 0;
        }
        
        return (($moisActuel - $moisPrecedent) / $moisPrecedent) * 100;
    }
}
