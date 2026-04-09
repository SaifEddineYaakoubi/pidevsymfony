<?php

namespace App\Repository;

use App\Entity\Culture;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CultureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Culture::class);
    }

    /**
     * @return Culture[]
     */
    public function searchByQuery(?string $q, ?string $sort = null, ?string $dir = null): array
    {
        $qb = $this->createQueryBuilder('c')
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
                    '(LOWER(c.type_culture) LIKE :' . $param . ' OR LOWER(c.etat_croissance) LIKE :' . $param . ' OR LOWER(p.nom) LIKE :' . $param . ')'
                );
                $qb->setParameter($param, '%' . $token . '%');
            }
        }

        $dir = strtoupper((string) $dir);
        $dir = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'DESC';

        $sortMap = [
            'type' => 'c.type_culture',
            'etat' => 'c.etat_croissance',
            'parcelle' => 'p.nom',
            'plantation' => 'c.date_plantation',
            'recolte' => 'c.date_recolte_prevue',
        ];
        $sortKey = $sortMap[$sort ?? ''] ?? 'c.date_plantation';

        return $qb->orderBy($sortKey, $dir)->addOrderBy('c.id_culture', 'DESC')->getQuery()->getResult();
    }

    /**
     * Same as searchByQuery(), but scoped to a given owner (agriculteur) via the related parcelle.
     *
     * @return Culture[]
     */
    public function searchByQueryForUser(Utilisateur $user, ?string $q, ?string $sort = null, ?string $dir = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.id_parcelle', 'p')->addSelect('p')
            ->andWhere('p.id_user = :user')
            ->setParameter('user', $user);

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
                    '(LOWER(c.type_culture) LIKE :' . $param . ' OR LOWER(c.etat_croissance) LIKE :' . $param . ' OR LOWER(p.nom) LIKE :' . $param . ')'
                );
                $qb->setParameter($param, '%' . $token . '%');
            }
        }

        $dir = strtoupper((string) $dir);
        $dir = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'DESC';

        $sortMap = [
            'type' => 'c.type_culture',
            'etat' => 'c.etat_croissance',
            'parcelle' => 'p.nom',
            'plantation' => 'c.date_plantation',
            'recolte' => 'c.date_recolte_prevue',
        ];
        $sortKey = $sortMap[$sort ?? ''] ?? 'c.date_plantation';

        return $qb->orderBy($sortKey, $dir)->addOrderBy('c.id_culture', 'DESC')->getQuery()->getResult();
    }

    /**
     * Stats by growth state for the current filter (same semantics as searchByQuery).
     *
     * @return array<string, int> e.g. ['germination'=>2, 'croissance'=>1, ...]
     */
    public function countByEtatCroissance(?string $q): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.etat_croissance AS etat, COUNT(c.id_culture) AS nb')
            ->leftJoin('c.id_parcelle', 'p')
            ->groupBy('c.etat_croissance');

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
                    '(LOWER(c.type_culture) LIKE :' . $param . ' OR LOWER(c.etat_croissance) LIKE :' . $param . ' OR LOWER(p.nom) LIKE :' . $param . ')'
                );
                $qb->setParameter($param, '%' . $token . '%');
            }
        }

        $rows = $qb->getQuery()->getArrayResult();
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['etat']] = (int) $row['nb'];
        }
        return $out;
    }

    /**
     * Same as countByEtatCroissance(), but scoped to a given owner (agriculteur) via parcelle.
     *
     * @return array<string, int>
     */
    public function countByEtatCroissanceForUser(Utilisateur $user, ?string $q): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.etat_croissance AS etat, COUNT(c.id_culture) AS nb')
            ->leftJoin('c.id_parcelle', 'p')
            ->andWhere('p.id_user = :user')
            ->setParameter('user', $user)
            ->groupBy('c.etat_croissance');

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
                    '(LOWER(c.type_culture) LIKE :' . $param . ' OR LOWER(c.etat_croissance) LIKE :' . $param . ' OR LOWER(p.nom) LIKE :' . $param . ')'
                );
                $qb->setParameter($param, '%' . $token . '%');
            }
        }

        $rows = $qb->getQuery()->getArrayResult();
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['etat']] = (int) $row['nb'];
        }
        return $out;
    }

    public function countMaturite(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id_culture)')
            ->andWhere('c.etat_croissance = :etat')
            ->setParameter('etat', 'maturite')
            ->getQuery()
            ->getSingleScalarResult();
    }
}