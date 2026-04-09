<?php

namespace App\Repository;

use App\Entity\Client;
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
}