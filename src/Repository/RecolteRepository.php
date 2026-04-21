<?php

namespace App\Repository;

use App\Entity\Recolte;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RecolteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recolte::class);
    }

<<<<<<< Updated upstream
    // Add custom methods as needed
}
=======
    /**
     * Build the base list query with optional search.
     */
    public function createListQueryBuilder(?string $search): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r');

        $search = trim((string) $search);
        if ($search !== '') {
            $qb->andWhere('r.type_culture LIKE :q OR r.localisation LIKE :q')
               ->setParameter('q', '%' . $search . '%');
        }

        return $qb;
    }

    public function createListQueryBuilderForUser(Utilisateur $user, ?string $search): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.id_user = :uid')
            ->setParameter('uid', $user->getIdUser());

        $search = trim((string) $search);
        if ($search !== '') {
            $qb->andWhere('r.type_culture LIKE :q OR r.localisation LIKE :q')
                ->setParameter('q', '%' . $search . '%');
        }

        return $qb;
    }

    /**
     * Return recoltes for list screens with search+sort.
     */
    public function findForIndex(?string $search, string $sort = 'date_desc'): array
    {
        $qb = $this->createListQueryBuilder($search);

        switch ($sort) {
            case 'date_asc':
                $qb->orderBy('r.date_recolte', 'ASC');
                break;
            case 'type_asc':
                $qb->orderBy('r.type_culture', 'ASC');
                break;
            case 'type_desc':
                $qb->orderBy('r.type_culture', 'DESC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('r.date_recolte', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    public function findForIndexForUser(Utilisateur $user, ?string $search, string $sort = 'date_desc'): array
    {
        $qb = $this->createListQueryBuilderForUser($user, $search);

        switch ($sort) {
            case 'date_asc':
                $qb->orderBy('r.date_recolte', 'ASC');
                break;
            case 'type_asc':
                $qb->orderBy('r.type_culture', 'ASC');
                break;
            case 'type_desc':
                $qb->orderBy('r.type_culture', 'DESC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('r.date_recolte', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Stats for header cards + repartition bar.
     *
     * @return array{
     *   total_filtered:int,
     *   sum_quantite:float,
     *   last_date:?\DateTimeInterface,
     *   counts_by_qualite: array<string,int>
     * }
     */
    public function getIndexStats(?string $search): array
    {
        // total + sum + last date
        $qb = $this->createListQueryBuilder($search)
            ->select('COUNT(r.id_recolte) AS total', 'COALESCE(SUM(r.quantite), 0) AS sumQte', 'MAX(r.date_recolte) AS lastDate');

        $row = $qb->getQuery()->getOneOrNullResult();

        // counts by qualite (group by)
        $qb2 = $this->createListQueryBuilder($search)
            ->select('r.qualite AS qualite', 'COUNT(r.id_recolte) AS c')
            ->groupBy('r.qualite');

        $rows = $qb2->getQuery()->getArrayResult();
        $counts = [];
        foreach ($rows as $r) {
            $key = (string) ($r['qualite'] ?? '');
            $key = $key !== '' ? $key : 'inconnue';
            $counts[$key] = (int) ($r['c'] ?? 0);
        }

        return [
            'total_filtered' => (int) ($row['total'] ?? 0),
            'sum_quantite' => (float) ($row['sumQte'] ?? 0),
            'last_date' => $row['lastDate'] ?? null,
            'counts_by_qualite' => $counts,
        ];
    }

    public function getIndexStatsForUser(Utilisateur $user, ?string $search): array
    {
        $qb = $this->createListQueryBuilderForUser($user, $search)
            ->select('COUNT(r.id_recolte) AS total', 'COALESCE(SUM(r.quantite), 0) AS sumQte', 'MAX(r.date_recolte) AS lastDate');

        $row = $qb->getQuery()->getOneOrNullResult();

        $qb2 = $this->createListQueryBuilderForUser($user, $search)
            ->select('r.qualite AS qualite', 'COUNT(r.id_recolte) AS c')
            ->groupBy('r.qualite');

        $rows = $qb2->getQuery()->getArrayResult();
        $counts = [];
        foreach ($rows as $r) {
            $key = (string) ($r['qualite'] ?? '');
            $key = $key !== '' ? $key : 'inconnue';
            $counts[$key] = (int) ($r['c'] ?? 0);
        }

        return [
            'total_filtered' => (int) ($row['total'] ?? 0),
            'sum_quantite' => (float) ($row['sumQte'] ?? 0),
            'last_date' => $row['lastDate'] ?? null,
            'counts_by_qualite' => $counts,
        ];
    }

    public function findOneForUser(int $id, Utilisateur $user): ?Recolte
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.id_culture', 'c')->addSelect('c')
            ->leftJoin('c.id_parcelle', 'p')->addSelect('p')
            ->andWhere('r.id_recolte = :id')
            ->andWhere('r.id_user = :userId')
            ->setParameter('id', $id)
            ->setParameter('userId', $user->getIdUser())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Recolte[]
     */
    public function searchByQuery(?string $q, ?string $sort = null, ?string $dir = null): array
    {
        $qb = $this->createQueryBuilder('r')
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
                    '(LOWER(r.type_culture) LIKE :' . $param . ' OR LOWER(r.localisation) LIKE :' . $param . ' OR LOWER(r.qualite) LIKE :' . $param . ' OR LOWER(c.type_culture) LIKE :' . $param . ' OR LOWER(p.nom) LIKE :' . $param . ')'
                );
                $qb->setParameter($param, '%' . $token . '%');
            }
        }

        $dir = strtoupper((string) $dir);
        $dir = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'DESC';

        $sortMap = [
            'type' => 'r.type_culture',
            'date' => 'r.date_recolte',
            'quantite' => 'r.quantite',
            'qualite' => 'r.qualite',
            'localisation' => 'r.localisation',
        ];
        $sortKey = $sortMap[$sort ?? ''] ?? 'r.date_recolte';

        return $qb->orderBy($sortKey, $dir)->addOrderBy('r.id_recolte', 'DESC')->getQuery()->getResult();
    }

    /**
     * Stats by quality for the current filter (same semantics as searchByQuery).
     *
     * @return array<string, int> e.g. ['bonne'=>2, 'moyenne'=>1, ...]
     */
    public function countByQualite(?string $q): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.qualite AS qualite, COUNT(r.id_recolte) AS nb')
            ->leftJoin('r.id_culture', 'c')
            ->leftJoin('c.id_parcelle', 'p')
            ->groupBy('r.qualite');

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
                    '(LOWER(r.type_culture) LIKE :' . $param . ' OR LOWER(r.localisation) LIKE :' . $param . ' OR LOWER(r.qualite) LIKE :' . $param . ' OR LOWER(c.type_culture) LIKE :' . $param . ' OR LOWER(p.nom) LIKE :' . $param . ')'
                );
                $qb->setParameter($param, '%' . $token . '%');
            }
        }

        $rows = $qb->getQuery()->getArrayResult();
        $out = [];
        foreach ($rows as $row) {
            $key = (string) ($row['qualite'] ?? '');
            $key = $key !== '' ? $key : 'inconnue';
            $out[$key] = (int) $row['nb'];
        }
        return $out;
    }
}

>>>>>>> Stashed changes
