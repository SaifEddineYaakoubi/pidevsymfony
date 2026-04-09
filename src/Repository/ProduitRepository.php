<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function findBySearchAndSort(?string $search, ?string $searchField, string $sort = 'idProduit', string $direction = 'ASC'): array
    {
        $fields = [
            'idProduit' => 'p.id_produit',
            'nom' => 'p.nom',
            'type' => 'p.type',
            'unite' => 'p.unite',
            'prixUnitaire' => 'p.prix_unitaire',
            'idUser' => 'p.id_user',
        ];

        $searchFields = [
            'nom' => 'p.nom',
            'type' => 'p.type',
            'unite' => 'p.unite',
        ];

        $sortColumn = $fields[$sort] ?? $fields['idProduit'];
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->createQueryBuilder('p')
            ->orderBy($sortColumn, $direction);

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
}