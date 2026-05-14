<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** 
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function findBySearchAndSort(?string $search, ?string $searchField, string $sort = 'idProduit', string $direction = 'ASC', ?\App\Entity\Utilisateur $user = null): array
    {
        $fields = [
            'idProduit' => 'p.id_produit',
            'nom' => 'p.nom',
            'type' => 'p.type',
            'unite' => 'p.unite',
            'prixUnitaire' => 'p.prix_unitaire',
            'idUser' => 'u.email',
        ];

        $searchFields = [
            'nom' => 'p.nom',
            'type' => 'p.type',
            'unite' => 'p.unite',
        ];

        $sortColumn = $fields[$sort] ?? $fields['idProduit'];
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.utilisateur', 'u')
            ->addSelect('u')
            ->orderBy($sortColumn, $direction);

        // Filtrer par utilisateur si fourni (responsable_stock voit uniquement ses produits)
        if ($user !== null) {
            $qb->andWhere('p.utilisateur = :user')
               ->setParameter('user', $user);
        }

        if ($search) {
            if (isset($searchFields[$searchField])) {
                $qb->andWhere($searchFields[$searchField] . ' LIKE :search')
                    ->setParameter('search', '%'.$search.'%');
            } else {
                $qb->andWhere('p.nom LIKE :search OR p.type LIKE :search OR p.unite LIKE :search')
                    ->setParameter('search', '%'.$search.'%');
            }
        }

        return $qb->getQuery()->getResult();
    }

    public function findBySearchAndSortForUser(\App\Entity\Utilisateur $user, ?string $search, ?string $searchField, string $sort = 'idProduit', string $direction = 'ASC'): array
    {
        return $this->findBySearchAndSort($search, $searchField, $sort, $direction, $user);
    }

    public function countByType(?string $search = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.type, COUNT(p.id_produit) as count')
            ->groupBy('p.type');

        if ($search) {
            $qb->andWhere('p.nom LIKE :search OR p.type LIKE :search OR p.unite LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        $results = $qb->getQuery()->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['type']] = (int) $result['count'];
        }

        return $counts;
    }
}