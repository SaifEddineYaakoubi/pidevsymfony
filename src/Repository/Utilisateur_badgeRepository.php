<?php

namespace App\Repository;

use App\Entity\Utilisateur_badge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** 
 * @extends ServiceEntityRepository<Utilisateur_badge>
 */
class Utilisateur_badgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur_badge::class);
    }

    // Add custom methods as needed
}