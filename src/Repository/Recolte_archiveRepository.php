<?php

namespace App\Repository;

use App\Entity\Recolte_archive;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/** 
 * @extends ServiceEntityRepository<Recolte_archive>
 */
class Recolte_archiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recolte_archive::class);
    }

    public function createListQueryBuilder(?string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');

        $q = trim((string) $q);
        if ($q !== '') {
            $qb->andWhere('a.type_culture LIKE :q OR a.localisation LIKE :q OR a.cause_supression LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        return $qb;
    }

    public function createListQueryBuilderForUser(Utilisateur $user, ?string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.id_user = :uid')
            ->setParameter('uid', $user->getIdUser());

        $q = trim((string) $q);
        if ($q !== '') {
            $qb->andWhere('a.type_culture LIKE :q OR a.localisation LIKE :q OR a.cause_supression LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        return $qb;
    }

    /**
     * @return Recolte_archive[]
     */
    public function findForIndex(?string $q, string $sort = 'archivage_desc'): array
    {
        $qb = $this->createListQueryBuilder($q);

        switch ($sort) {
            case 'archivage_asc':
                $qb->orderBy('a.date_archivage', 'ASC');
                break;
            case 'type_asc':
                $qb->orderBy('a.type_culture', 'ASC');
                break;
            case 'type_desc':
                $qb->orderBy('a.type_culture', 'DESC');
                break;
            case 'archivage_desc':
            default:
                $qb->orderBy('a.date_archivage', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Recolte_archive[]
     */
    public function findForIndexForUser(Utilisateur $user, ?string $q, string $sort = 'archivage_desc'): array
    {
        $qb = $this->createListQueryBuilderForUser($user, $q);

        switch ($sort) {
            case 'archivage_asc':
                $qb->orderBy('a.date_archivage', 'ASC');
                break;
            case 'type_asc':
                $qb->orderBy('a.type_culture', 'ASC');
                break;
            case 'type_desc':
                $qb->orderBy('a.type_culture', 'DESC');
                break;
            case 'archivage_desc':
            default:
                $qb->orderBy('a.date_archivage', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array{
     *   total_filtered:int,
     *   sum_quantite:float,
     *   last_archivage:?\DateTimeInterface,
     *   top_causes: array<int,array{cause:string,count:int}>,
     *   others_count:int
     * }
     */
    public function getIndexStats(?string $q, int $top = 3): array
    {
        $row = $this->createListQueryBuilder($q)
            ->select('COUNT(a.id_archive) AS total', 'COALESCE(SUM(a.quantite),0) AS sumQte', 'MAX(a.date_archivage) AS lastArch')
            ->getQuery()
            ->getOneOrNullResult();

        $rows = $this->createListQueryBuilder($q)
            ->select('a.cause_supression AS cause', 'COUNT(a.id_archive) AS c')
            ->groupBy('a.cause_supression')
            ->orderBy('c', 'DESC')
            ->setMaxResults($top)
            ->getQuery()
            ->getArrayResult();

        $topCauses = [];
        $topTotal = 0;
        foreach ($rows as $r) {
            $cause = trim((string) ($r['cause'] ?? ''));
            if ($cause === '') {
                $cause = 'Inconnue';
            }
            $count = (int) ($r['c'] ?? 0);
            $topTotal += $count;
            $topCauses[] = ['cause' => $cause, 'count' => $count];
        }

        $total = (int) ($row['total'] ?? 0);

        return [
            'total_filtered' => $total,
            'sum_quantite' => (float) ($row['sumQte'] ?? 0),
            'last_archivage' => $row['lastArch'] ?? null,
            'top_causes' => $topCauses,
            'others_count' => max(0, $total - $topTotal),
        ];
    }

    public function getIndexStatsForUser(Utilisateur $user, ?string $q, int $top = 3): array
    {
        $row = $this->createListQueryBuilderForUser($user, $q)
            ->select('COUNT(a.id_archive) AS total', 'COALESCE(SUM(a.quantite),0) AS sumQte', 'MAX(a.date_archivage) AS lastArch')
            ->getQuery()
            ->getOneOrNullResult();

        $rows = $this->createListQueryBuilderForUser($user, $q)
            ->select('a.cause_supression AS cause', 'COUNT(a.id_archive) AS c')
            ->groupBy('a.cause_supression')
            ->orderBy('c', 'DESC')
            ->setMaxResults($top)
            ->getQuery()
            ->getArrayResult();

        $topCauses = [];
        $topTotal = 0;
        foreach ($rows as $r) {
            $cause = trim((string) ($r['cause'] ?? ''));
            if ($cause === '') {
                $cause = 'Inconnue';
            }
            $count = (int) ($r['c'] ?? 0);
            $topTotal += $count;
            $topCauses[] = ['cause' => $cause, 'count' => $count];
        }

        $total = (int) ($row['total'] ?? 0);

        return [
            'total_filtered' => $total,
            'sum_quantite' => (float) ($row['sumQte'] ?? 0),
            'last_archivage' => $row['lastArch'] ?? null,
            'top_causes' => $topCauses,
            'others_count' => max(0, $total - $topTotal),
        ];
    }

    public function findOneForUser(int $id, Utilisateur $user): ?Recolte_archive
    {
        $a = $this->findOneBy([
            'id_archive' => $id,
            'id_user' => $user->getIdUser(),
        ]);

        return $a instanceof Recolte_archive ? $a : null;
    }
}