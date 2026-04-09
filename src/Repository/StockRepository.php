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

        // Important: on renvoie des tableaux (getArrayResult) pour éviter les proxies Doctrine
        // cassés quand la FK pointe sur un produit supprimé (sinon Twig déclenche EntityNotFoundException).
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.id_produit', 'p')
            ->addSelect('p')
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

        /** @var array<int, array<string, mixed>> $results */
        $results = $qb->getQuery()->getArrayResult();

        // PHP-based sorting for duration (calculate days between entry and expiration)
        if (isset($sortConfig['php']) && $sortConfig['php']) {
            usort($results, function (array $a, array $b) use ($sortConfig): int {
                $dateEntreeA = $a['date_entree'] ?? null;
                $dateExpA = $a['date_expiration'] ?? null;
                $dateEntreeB = $b['date_entree'] ?? null;
                $dateExpB = $b['date_expiration'] ?? null;

                $durationA = ($dateEntreeA instanceof \DateTimeInterface && $dateExpA instanceof \DateTimeInterface)
                    ? $dateExpA->diff($dateEntreeA)->days
                    : PHP_INT_MAX;
                $durationB = ($dateEntreeB instanceof \DateTimeInterface && $dateExpB instanceof \DateTimeInterface)
                    ? $dateExpB->diff($dateEntreeB)->days
                    : PHP_INT_MAX;

                $comparison = $durationA <=> $durationB;
                return $sortConfig['direction'] === 'DESC' ? -$comparison : $comparison;
            });
        }

        return $results;
    }
}