<?php

namespace App\Repository;

use App\Entity\Vente;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** 
 * @extends ServiceEntityRepository<Vente>
 */
class VenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vente::class);
    }

    /**
     * Recherche et tri (Version ADMIN)
     */
    public function findBySearchAndSort(?string $search = null, string $sortBy = 'date_vente', string $order = 'DESC'): array
    {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        // EL ISLAH HNA: Nesta3mlou el esmawet mta3 el Entity b-el underscore
        $sortableColumns = ['date_vente', 'montant_total', 'id_vente'];
        if (!in_array($sortBy, $sortableColumns, true)) {
            $sortBy = 'date_vente';
        }

        $qb = $this->createQueryBuilder('v')
            ->leftJoin('v.id_client', 'c') // S7i7a tawa (id_client kima f-el Entity)
            ->addSelect('c');

        if (!empty($search)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('v.id_vente', ':search'),
                    $qb->expr()->like('c.nom', ':search')
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('v.' . $sortBy, $order);

        return $qb->getQuery()->getResult();
    }

    /**
     * Stats globales (Version ADMIN)
     */
    public function getVenteStats(): array
    {
        $row = $this->createQueryBuilder('v')
            ->select(
                'COALESCE(SUM(v.montant_total), 0) AS total_revenus',
                'COUNT(v.id_vente) AS ventes_count',
                'MAX(v.date_vente) AS last_vente_date'
            )
            ->getQuery()
            ->getOneOrNullResult();

        $total = $row['total_revenus'] ?? 0;
        $count = $row['ventes_count'] ?? 0;

        return [
            'total_revenus' => (float) $total,
            'ventes_count' => (int) $count,
            'montant_moyen' => ($count > 0) ? ($total / $count) : 0,
            'last_vente_date' => $row['last_vente_date'] ?? null,
        ];
    }

    // --- Version FRONT (USER) ---

    public function findBySearchAndSortForUser(Utilisateur $user, ?string $search = null, string $sortBy = 'date_vente', string $order = 'DESC'): array
    {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.id_user = :user') // id_user kima f-el Entity
            ->setParameter('user', $user)
            ->leftJoin('v.id_client', 'c')
            ->addSelect('c');

        if (!empty($search)) {
            $qb->andWhere('c.nom LIKE :search')->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('v.' . $sortBy, $order);
        return $qb->getQuery()->getResult();
    }

    public function getVenteStatsForUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('v')
            ->select(
                'COALESCE(SUM(v.montant_total), 0) AS total_revenus',
                'COUNT(v.id_vente) AS ventes_count'
            )
            ->andWhere('v.id_user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Alias pour getVenteStats (pour compatibilité adminIndex)
     */
    public function getAllVenteStats(): array
    {
        return $this->getVenteStats();
    }
}