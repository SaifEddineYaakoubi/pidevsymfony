<?php

namespace App\Repository;

use App\Entity\User_badge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** 
 * @extends ServiceEntityRepository<User_badge>
 */
class User_badgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User_badge::class);
    }

    // Add custom methods as needed
}