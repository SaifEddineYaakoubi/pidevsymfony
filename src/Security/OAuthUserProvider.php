<?php


namespace App\Security;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class OAuthUserProvider implements OAuthAwareUserProviderInterface
{
private $entityManager;

public function __construct(EntityManagerInterface $entityManager)
{
$this->entityManager = $entityManager;
}

public function loadUserByOAuthUserResponse(UserResponseInterface $response): UserInterface
{
$email = $response->getEmail();

// 1. Vérifier si l'utilisateur existe déjà
$user = $this->entityManager
->getRepository(Utilisateur::class)
->findOneBy(['email' => $email]);

// 2. S'il n'existe pas, le créer
if (!$user) {
$user = new Utilisateur();
$user->setEmail($email);
// Générer un nom d'utilisateur à partir du nom Google
$user->setUsername($response->getNickname() ?? explode('@', $email)[0]);
$user->setNom($response->getFirstName());
$user->setPrenom($response->getLastName());
// Définir un rôle par défaut (par exemple, 'ROLE_USER')
$user->setRoles(['ROLE_USER']);
// Générer un mot de passe aléatoire (l'utilisateur se connectera via Google)
$user->setPassword(uniqid('google_', true));

$this->entityManager->persist($user);
$this->entityManager->flush();
}

return $user;
}
}