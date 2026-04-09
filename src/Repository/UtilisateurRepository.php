<?php
// src/Repository/UtilisateurRepository.php
namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    // CORRECTION : Utilisez PasswordAuthenticatedUserInterface au lieu de UserInterface
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            return;
        }

        $user->setMotDePasse($newHashedPassword);
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();
    }

    public function findOneByEmail(string $email): ?Utilisateur
    {
        return $this->findOneBy(['email' => $email]);
    }
}