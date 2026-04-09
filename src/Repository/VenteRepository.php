<?php

namespace App\Repository;

use App\Entity\Vente;
use App\Entity\Utilisateur;
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

    public function findBySearchAndSortForUser(Utilisateur $user, ?string $search = null, string $sortBy = 'date_vente', string $order = 'DESC'): array
    {
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $sortableColumns = ['date_vente', 'montant_total', 'id_vente'];
        if (!in_array($sortBy, $sortableColumns, true)) {
            $sortBy = 'date_vente';
        }

        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.id_user = :user')
            ->setParameter('user', $user);

        $qb->leftJoin('v.id_client', 'c')
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

    /**
     * Stats globales pour l'entête dashboard de la page index.
     *
     * @return array{total_revenus: float, ventes_count: int, last_vente_date: ?\DateTimeInterface}
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

        return [
            'total_revenus' => isset($row['total_revenus']) ? (float) $row['total_revenus'] : 0.0,
            'ventes_count' => isset($row['ventes_count']) ? (int) $row['ventes_count'] : 0,
            'last_vente_date' => $row['last_vente_date'] ?? null,
        ];
    }

    public function getVenteStatsForUser(Utilisateur $user): array
    {
        $row = $this->createQueryBuilder('v')
            ->select(
                'COALESCE(SUM(v.montant_total), 0) AS total_revenus',
                'COUNT(v.id_vente) AS ventes_count',
                'MAX(v.date_vente) AS last_vente_date'
            )
            ->andWhere('v.id_user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'total_revenus' => isset($row['total_revenus']) ? (float) $row['total_revenus'] : 0.0,
            'ventes_count' => isset($row['ventes_count']) ? (int) $row['ventes_count'] : 0,
            'last_vente_date' => $row['last_vente_date'] ?? null,
        ];
    }

    public function findOneForUser(int $id, Utilisateur $user): ?Vente
    {
        $v = $this->findOneBy([
            'id_vente' => $id,
            'id_user' => $user,
        ]);

        return $v instanceof Vente ? $v : null;
    }
}