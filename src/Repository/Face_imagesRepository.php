<?php

namespace App\Repository;

use App\Entity\Face_images;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** 
 * @extends ServiceEntityRepository<Face_images>
 */
class Face_imagesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Face_images::class);
    }

    // Add custom methods as needed
}