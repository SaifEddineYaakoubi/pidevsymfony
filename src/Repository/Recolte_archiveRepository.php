<?php

namespace App\Repository;

use App\Entity\Recolte_archive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class Recolte_archiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recolte_archive::class);
    }

    // Add custom methods as needed
}