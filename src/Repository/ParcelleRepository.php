<?php

namespace App\Repository;

use App\Entity\Parcelle;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParcelleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Parcelle::class);
    }

    /**
     * @return Parcelle[]
     */
    public function searchByQuery(?string $q, ?string $sort = null, ?string $dir = null): array
    {
        $qb = $this->createQueryBuilder('p');

        $q = trim((string) $q);
        if ($q !== '') {
            $qNorm = mb_strtolower($q);
            $tokens = preg_split('/\s+/', $qNorm) ?: [];

            // AND between tokens, OR between fields
            foreach ($tokens as $i => $token) {
                if ($token === '') {
                    continue;
                }
                $param = 't' . $i;
                $qb->andWhere(
                    '(LOWER(p.nom) LIKE :' . $param . ' OR LOWER(p.localisation) LIKE :' . $param . ' OR LOWER(p.etat) LIKE :' . $param . ')'
                );
                $qb->setParameter($param, '%' . $token . '%');
            }
        }

        $dir = strtoupper((string) $dir);
        $dir = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'ASC';

        $sortMap = [
            'nom' => 'p.nom',
            'superficie' => 'p.superficie',
            'localisation' => 'p.localisation',
            'etat' => 'p.etat',
        ];
        $sortKey = $sortMap[$sort ?? ''] ?? 'p.nom';

        return $qb->orderBy($sortKey, $dir)->addOrderBy('p.id_parcelle', 'DESC')->getQuery()->getResult();
    }

    /**
     * Same as searchByQuery(), but scoped to a given owner (agriculteur).
     *
     * @return Parcelle[]
     */
    public function searchByQueryForUser(Utilisateur $user, ?string $q, ?string $sort = null, ?string $dir = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.id_user = :user')
            ->setParameter('user', $user);

        $q = trim((string) $q);
        if ($q !== '') {
            $qNorm = mb_strtolower($q);
            $tokens = preg_split('/\s+/', $qNorm) ?: [];

            // AND between tokens, OR between fields
            foreach ($tokens as $i => $token) {
                if ($token === '') {
                    continue;
                }
                $param = 't' . $i;
                $qb->andWhere(
                    '(LOWER(p.nom) LIKE :' . $param . ' OR LOWER(p.localisation) LIKE :' . $param . ' OR LOWER(p.etat) LIKE :' . $param . ')'
                );
                $qb->setParameter($param, '%' . $token . '%');
            }
        }

        $dir = strtoupper((string) $dir);
        $dir = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'ASC';

        $sortMap = [
            'nom' => 'p.nom',
            'superficie' => 'p.superficie',
            'localisation' => 'p.localisation',
            'etat' => 'p.etat',
        ];
        $sortKey = $sortMap[$sort ?? ''] ?? 'p.nom';

        return $qb->orderBy($sortKey, $dir)->addOrderBy('p.id_parcelle', 'DESC')->getQuery()->getResult();
    }

    /**
     * Stats by parcelle state for the current filter (same semantics as searchByQuery).
     *
     * @return array<string, int> e.g. ['active'=>2, 'repos'=>1, ...]
     */
    public function countByEtat(?string $q): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.etat AS etat, COUNT(p.id_parcelle) AS nb')
            ->groupBy('p.etat');

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
                    '(LOWER(p.nom) LIKE :' . $param . ' OR LOWER(p.localisation) LIKE :' . $param . ' OR LOWER(p.etat) LIKE :' . $param . ')'
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
     * Same as countByEtat(), but scoped to a given owner (agriculteur).
     *
     * @return array<string, int>
     */
    public function countByEtatForUser(Utilisateur $user, ?string $q): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p.etat AS etat, COUNT(p.id_parcelle) AS nb')
            ->andWhere('p.id_user = :user')
            ->setParameter('user', $user)
            ->groupBy('p.etat');

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
                    '(LOWER(p.nom) LIKE :' . $param . ' OR LOWER(p.localisation) LIKE :' . $param . ' OR LOWER(p.etat) LIKE :' . $param . ')'
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
     * @return Parcelle|null
     */
    public function findOneForUser(int $id, Utilisateur $user)
    {
        $p = $this->findOneBy([
            'id_parcelle' => $id,
            'id_user' => $user,
        ]);

        if ($p === null) {
            return null;
        }

        return $p instanceof Parcelle ? $p : null;
    }
}