<?php

namespace App\Repository;

use App\Entity\Soil_analysis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** 
 * @extends ServiceEntityRepository<Soil_analysis>
 */
class Soil_analysisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Soil_analysis::class);
    }

    // Add custom methods as needed
}