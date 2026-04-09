<?php

namespace App\Repository;

use App\Entity\Vente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class VenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vente::class);
    }

    /**
     * Recherche par nom de client ou ID et tri les ventes
     */
    public function findBySearchAndSort(?string $search = null, string $sortBy = 'date_vente', string $order = 'DESC'): array
    {
        // 1. Valider l'ordre pour éviter les injections SQL
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        // 2. Liste des colonnes triables autorisées
        $sortableColumns = ['date_vente', 'montant_total', 'id_vente'];
        if (!in_array($sortBy, $sortableColumns, true)) {
            $sortBy = 'date_vente';
        }

        $qb = $this->createQueryBuilder('v');

        // 3. Joindre la table Client (c) pour accéder au nom
        $qb->leftJoin('v.id_client', 'c')
           ->addSelect('c'); // Optimization: évite le problème N+1

        // 4. Ajouter la recherche par ID de vente OU par nom du client
        if (!empty($search)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('v.id_vente', ':search'),
                    $qb->expr()->like('c.nom', ':search')
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }

        // 5. Ajouter le tri
        $qb->orderBy('v.' . $sortBy, $order);

        return $qb->getQuery()->getResult();
    }

    /**
     * Obtenir toutes les ventes triées avec le client chargé
     */
    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.id_client', 'c')
            ->addSelect('c')
            ->orderBy('v.date_vente', 'DESC')
            ->addOrderBy('v.id_vente', 'DESC')
            ->getQuery()
            ->getResult();
    }
}