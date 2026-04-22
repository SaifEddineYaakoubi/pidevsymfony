<?php

namespace App\Repository;

use App\Entity\Client;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Orx;

class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    /**
     * Recherche et tri les clients avec createQueryBuilder
     *
     * @param string|null $search Terme de recherche (filtrer par nom)
     * @param string $sortBy Colonne de tri (par défaut 'nom')
     * @param string $order Ordre de tri (ASC ou DESC)
     *
     * @return array
     */
    public function findBySearchAndSort(?string $search = null, string $sortBy = 'nom', string $order = 'ASC'): array
    {
        // Valider l'ordre pour éviter les injections SQL
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        // Liste des colonnes triables autorisées
        $sortableColumns = ['nom', 'contact', 'id_client'];
        if (!in_array($sortBy, $sortableColumns, true)) {
            $sortBy = 'nom';
        }

        $qb = $this->createQueryBuilder('c');

        // Ajouter la recherche par nom si fournie
        if (!empty($search)) {
            $qb->where('c.nom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Ajouter le tri
        $qb->orderBy('c.' . $sortBy, $order);

        return $qb->getQuery()->getResult();
    }

    public function findBySearchAndSortForUser(Utilisateur $user, ?string $search = null, string $sortBy = 'nom', string $order = 'ASC'): array
    {
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        $sortableColumns = ['nom', 'contact', 'id_client'];
        if (!in_array($sortBy, $sortableColumns, true)) {
            $sortBy = 'nom';
        }

        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.id_user = :uid')
            ->setParameter('uid', $user->getIdUser());

        if (!empty($search)) {
            $qb->andWhere('c.nom LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('c.' . $sortBy, $order);

        return $qb->getQuery()->getResult();
    }

    /**
     * Obtenir tous les clients triés par défaut par nom
     *
     * @return array
     */
    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Stats globales pour l'entête dashboard de la page index.
     *
     * @return array{clients_count: int, last_client_id: ?int}
     */
    public function getClientStats(): array
    {
        $row = $this->createQueryBuilder('c')
            ->select(
                'COUNT(c.id_client) AS clients_count',
                'MAX(c.id_client) AS last_client_id'
            )
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'clients_count' => isset($row['clients_count']) ? (int) $row['clients_count'] : 0,
            'last_client_id' => $row['last_client_id'] !== null ? (int) $row['last_client_id'] : null,
        ];
    }

    public function getClientStatsForUser(Utilisateur $user): array
    {
        $row = $this->createQueryBuilder('c')
            ->select(
                'COUNT(c.id_client) AS clients_count',
                'MAX(c.id_client) AS last_client_id'
            )
            ->andWhere('c.id_user = :uid')
            ->setParameter('uid', $user->getIdUser())
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'clients_count' => isset($row['clients_count']) ? (int) $row['clients_count'] : 0,
            'last_client_id' => $row['last_client_id'] !== null ? (int) $row['last_client_id'] : null,
        ];
    }

    public function findOneForUser(int $id, Utilisateur $user): ?Client
    {
        $c = $this->findOneBy([
            'id_client' => $id,
            'id_user' => $user->getIdUser(),
        ]);

        return $c instanceof Client ? $c : null;
    }

    /**
     * Alias pour getClientStats (pour compatibilité adminIndex)
     */
    public function getAllClientStats(): array
    {
        return $this->getClientStats();
    }
}