<?php

namespace App\Repository;

use App\Entity\Rendement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RendementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rendement::class);
    }

<<<<<<< Updated upstream
    // Add custom methods as needed
}
=======
    public function createListQueryBuilder(?string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('re')
            ->leftJoin('re.id_recolte', 'r')
            ->addSelect('r');

        $q = trim((string) $q);
        if ($q !== '') {
            $qb->andWhere('r.type_culture LIKE :q OR r.localisation LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        return $qb;
    }

    public function createListQueryBuilderForUser(Utilisateur $user, ?string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('re')
            ->leftJoin('re.id_recolte', 'r')
            ->addSelect('r')
            ->andWhere('r.id_user = :uid')
            ->setParameter('uid', $user->getIdUser());

        $q = trim((string) $q);
        if ($q !== '') {
            $qb->andWhere('r.type_culture LIKE :q OR r.localisation LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        return $qb;
    }

    public function findForIndex(?string $q, string $sort = 'prod_desc'): array
    {
        $qb = $this->createListQueryBuilder($q);

        switch ($sort) {
            case 'prod_asc':
                $qb->orderBy('re.productivite', 'ASC');
                break;
            case 'prod_desc':
                $qb->orderBy('re.productivite', 'DESC');
                break;
            case 'date_desc':
                $qb->orderBy('r.date_recolte', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('r.date_recolte', 'ASC');
                break;
            default:
                $qb->orderBy('re.productivite', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    public function findForIndexForUser(Utilisateur $user, ?string $q, string $sort = 'prod_desc'): array
    {
        $qb = $this->createListQueryBuilderForUser($user, $q);

        switch ($sort) {
            case 'prod_asc':
                $qb->orderBy('re.productivite', 'ASC');
                break;
            case 'prod_desc':
                $qb->orderBy('re.productivite', 'DESC');
                break;
            case 'date_desc':
                $qb->orderBy('r.date_recolte', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('r.date_recolte', 'ASC');
                break;
            default:
                $qb->orderBy('re.productivite', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array{
     *   total_filtered:int,
     *   avg_productivite:float,
     *   max_productivite:float,
     *   counts_by_class: array<string,int>
     * }
     */
    public function getIndexStats(?string $q): array
    {
        $row = $this->createListQueryBuilder($q)
            ->select(
                'COUNT(re.id_rendement) AS total',
                'COALESCE(AVG(re.productivite), 0) AS avgProd',
                'COALESCE(MAX(re.productivite), 0) AS maxProd'
            )
            ->getQuery()
            ->getOneOrNullResult();

        // productivity classes (default thresholds):
        //  - faible: < 1
        //  - moyen: 1 - < 2
        //  - bon: 2 - < 3
        //  - eleve: >= 3
        $base = $this->createListQueryBuilder($q);

        $counts = [
            'faible' => 0,
            'moyen' => 0,
            'bon' => 0,
            'eleve' => 0,
        ];

        $c1 = (int) $base->select('COUNT(re.id_rendement)')
            ->andWhere('re.productivite < 1')
            ->getQuery()->getSingleScalarResult();
        $counts['faible'] = $c1;

        $c2 = (int) $this->createListQueryBuilder($q)->select('COUNT(re.id_rendement)')
            ->andWhere('re.productivite >= 1 AND re.productivite < 2')
            ->getQuery()->getSingleScalarResult();
        $counts['moyen'] = $c2;

        $c3 = (int) $this->createListQueryBuilder($q)->select('COUNT(re.id_rendement)')
            ->andWhere('re.productivite >= 2 AND re.productivite < 3')
            ->getQuery()->getSingleScalarResult();
        $counts['bon'] = $c3;

        $c4 = (int) $this->createListQueryBuilder($q)->select('COUNT(re.id_rendement)')
            ->andWhere('re.productivite >= 3')
            ->getQuery()->getSingleScalarResult();
        $counts['eleve'] = $c4;

        return [
            'total_filtered' => (int) ($row['total'] ?? 0),
            'avg_productivite' => (float) ($row['avgProd'] ?? 0),
            'max_productivite' => (float) ($row['maxProd'] ?? 0),
            'counts_by_class' => $counts,
        ];
    }

    public function getIndexStatsForUser(Utilisateur $user, ?string $q): array
    {
        $row = $this->createListQueryBuilderForUser($user, $q)
            ->select(
                'COUNT(re.id_rendement) AS total',
                'COALESCE(AVG(re.productivite), 0) AS avgProd',
                'COALESCE(MAX(re.productivite), 0) AS maxProd'
            )
            ->getQuery()
            ->getOneOrNullResult();

        $counts = [
            'faible' => 0,
            'moyen' => 0,
            'bon' => 0,
            'eleve' => 0,
        ];

        $counts['faible'] = (int) $this->createListQueryBuilderForUser($user, $q)->select('COUNT(re.id_rendement)')
            ->andWhere('re.productivite < 1')
            ->getQuery()->getSingleScalarResult();

        $counts['moyen'] = (int) $this->createListQueryBuilderForUser($user, $q)->select('COUNT(re.id_rendement)')
            ->andWhere('re.productivite >= 1 AND re.productivite < 2')
            ->getQuery()->getSingleScalarResult();

        $counts['bon'] = (int) $this->createListQueryBuilderForUser($user, $q)->select('COUNT(re.id_rendement)')
            ->andWhere('re.productivite >= 2 AND re.productivite < 3')
            ->getQuery()->getSingleScalarResult();

        $counts['eleve'] = (int) $this->createListQueryBuilderForUser($user, $q)->select('COUNT(re.id_rendement)')
            ->andWhere('re.productivite >= 3')
            ->getQuery()->getSingleScalarResult();

        return [
            'total_filtered' => (int) ($row['total'] ?? 0),
            'avg_productivite' => (float) ($row['avgProd'] ?? 0),
            'max_productivite' => (float) ($row['maxProd'] ?? 0),
            'counts_by_class' => $counts,
        ];
    }

    public function findOneForUser(int $id, Utilisateur $user): ?Rendement
    {
        $qb = $this->createQueryBuilder('re')
            ->leftJoin('re.id_recolte', 'r')
            ->addSelect('r')
            ->andWhere('re.id_rendement = :id')
            ->andWhere('r.id_user = :uid')
            ->setParameter('id', $id)
            ->setParameter('uid', $user->getIdUser())
            ->setMaxResults(1);

        $res = $qb->getQuery()->getOneOrNullResult();
        return $res instanceof Rendement ? $res : null;
    }

    /**
     * Find all rendements with valid (non-deleted) recoltes
     * @return Rendement[]
     */
    public function findAllWithValidRecoltes(): array
    {
        return $this->createQueryBuilder('re')
            ->innerJoin('re.id_recolte', 'r')
            ->addSelect('r')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Rendement[]
     */
    public function searchByQuery(?string $q, ?string $sort = null, ?string $dir = null): array
    {
        $qb = $this->createQueryBuilder('re')
            ->leftJoin('re.id_recolte', 'r')->addSelect('r')
            ->leftJoin('r.id_culture', 'c')->addSelect('c')
            ->leftJoin('c.id_parcelle', 'p')->addSelect('p');

        $q = trim((string) $q);
        if ($q !== '') {
            $qNorm = mb_strtolower($q);
            $tokens = preg_split('/\s+/', $qNorm) ?: [];

            foreach ($tokens as $i => $token) {
                if ($token === '') {
                    continue;
                }
                $param = 't' . $i;
                $qb->andWhere(
                    '(LOWER(r.type_culture) LIKE :' . $param . ' OR LOWER(r.localisation) LIKE :' . $param . ' OR LOWER(c.type_culture) LIKE :' . $param . ' OR LOWER(p.nom) LIKE :' . $param . ')'
                );
                $qb->setParameter($param, '%' . $token . '%');
            }
        }

        $dir = strtoupper((string) $dir);
        $dir = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'DESC';

        $sortMap = [
            'productivite' => 're.productivite',
            'date' => 'r.date_recolte',
            'type' => 'r.type_culture',
            'localisation' => 'r.localisation',
        ];
        $sortKey = $sortMap[$sort ?? ''] ?? 're.productivite';

        return $qb->orderBy($sortKey, $dir)->addOrderBy('re.id_rendement', 'DESC')->getQuery()->getResult();
    }

    /**
     * Stats by productivity class for the current filter (same semantics as searchByQuery).
     *
     * @return array<string, int> e.g. ['faible'=>2, 'moyen'=>1, ...]
     */
    public function countByClass(?string $q): array
    {
        $qb = $this->createQueryBuilder('re')
            ->leftJoin('re.id_recolte', 'r')
            ->leftJoin('r.id_culture', 'c')
            ->leftJoin('c.id_parcelle', 'p');

        $q = trim((string) $q);
        if ($q !== '') {
            $qNorm = mb_strtolower($q);
            $tokens = preg_split('/\s+/', $qNorm) ?: [];

            foreach ($tokens as $i => $token) {
                if ($token === '') {
                    continue;
                }
                $param = 't' . $i;
                $qb->andWhere(
                    '(LOWER(r.type_culture) LIKE :' . $param . ' OR LOWER(r.localisation) LIKE :' . $param . ' OR LOWER(c.type_culture) LIKE :' . $param . ' OR LOWER(p.nom) LIKE :' . $param . ')'
                );
                $qb->setParameter($param, '%' . $token . '%');
            }
        }

        $counts = [
            'faible' => 0,
            'moyen' => 0,
            'bon' => 0,
            'eleve' => 0,
        ];

        $counts['faible'] = (int) (clone $qb)->select('COUNT(re.id_rendement)')
            ->andWhere('re.productivite < 1')
            ->getQuery()->getSingleScalarResult();

        $counts['moyen'] = (int) (clone $qb)->select('COUNT(re.id_rendement)')
            ->andWhere('re.productivite >= 1 AND re.productivite < 2')
            ->getQuery()->getSingleScalarResult();

        $counts['bon'] = (int) (clone $qb)->select('COUNT(re.id_rendement)')
            ->andWhere('re.productivite >= 2 AND re.productivite < 3')
            ->getQuery()->getSingleScalarResult();

        $counts['eleve'] = (int) (clone $qb)->select('COUNT(re.id_rendement)')
            ->andWhere('re.productivite >= 3')
            ->getQuery()->getSingleScalarResult();

        return $counts;
    }
}

>>>>>>> Stashed changes
