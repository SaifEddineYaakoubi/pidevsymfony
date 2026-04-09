<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    public function findBySearchAndSort(?string $search, ?string $searchField, string $sort = 'dateEntreeDesc'): array
    {
        $sortFields = [
            'dateEntreeAsc' => ['column' => 's.date_entree', 'direction' => 'ASC', 'dql' => true],
            'dateEntreeDesc' => ['column' => 's.date_entree', 'direction' => 'DESC', 'dql' => true],
            'dureeVieAsc' => ['column' => 's.date_expiration', 'direction' => 'ASC', 'dql' => true, 'php' => true],
            'dureeVieDesc' => ['column' => 's.date_expiration', 'direction' => 'DESC', 'dql' => true, 'php' => true],
            'quantiteAsc' => ['column' => 's.quantite', 'direction' => 'ASC', 'dql' => true],
            'quantiteDesc' => ['column' => 's.quantite', 'direction' => 'DESC', 'dql' => true],
            'idProduitAsc' => ['column' => 'p.nom', 'direction' => 'ASC', 'dql' => true],
            'idProduitDesc' => ['column' => 'p.nom', 'direction' => 'DESC', 'dql' => true],
        ];

        $searchFields = [
            'idProduit' => 'p.nom',
            'quantite' => 's.quantite',
            'dateEntree' => 's.date_entree',
            'dateExpiration' => 's.date_expiration',
            'idUser' => 's.id_user',
        ];

        $sortConfig = $sortFields[$sort] ?? $sortFields['dateEntreeDesc'];

        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.id_produit', 'p')
            ->orderBy($sortConfig['column'], $sortConfig['direction']);

        if ($search) {
            if (isset($searchFields[$searchField])) {
                $qb->andWhere($searchFields[$searchField] . ' LIKE :search')
                    ->setParameter('search', '%'.$search.'%');
            } else {
                $qb->andWhere('p.nom LIKE :search OR s.quantite LIKE :search OR s.id_user LIKE :search')
                    ->setParameter('search', '%'.$search.'%');
            }
        }

        $results = $qb->getQuery()->getResult();

        // PHP-based sorting for duration (calculate days between entry and expiration)
        if (isset($sortConfig['php']) && $sortConfig['php']) {
            usort($results, function ($a, $b) use ($sortConfig) {
                $durationA = $a->getDateEntree() && $a->getDateExpiration() 
                    ? $a->getDateExpiration()->diff($a->getDateEntree())->days 
                    : PHP_INT_MAX;
                $durationB = $b->getDateEntree() && $b->getDateExpiration() 
                    ? $b->getDateExpiration()->diff($b->getDateEntree())->days 
                    : PHP_INT_MAX;

                $comparison = $durationA <=> $durationB;
                return $sortConfig['direction'] === 'DESC' ? -$comparison : $comparison;
            });
        }

        return $results;
    }
}