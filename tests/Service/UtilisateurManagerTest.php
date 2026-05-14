<?php

namespace App\Tests\Service;

use App\Entity\Utilisateur;
use App\Service\UtilisateurManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UtilisateurManagerTest extends KernelTestCase
{
    private UtilisateurManager $utilisateurManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $validator = self::getContainer()->get('validator');
        $passwordHasher = self::getContainer()->get('security.password_hasher');
        $this->utilisateurManager = new UtilisateurManager($validator, $passwordHasher);
    }

    /**
     * Test creating a valid utilisateur
     */
    public function testCreateValidUtilisateur(): void
    {
        $user = $this->utilisateurManager->createUtilisateur(
            'Dupont',
            'Jean',
            'jean.dupont@example.com',
            'agriculteur',
            'SecurePassword123'
        );

        $this->assertInstanceOf(Utilisateur::class, $user);
        $this->assertEquals('Dupont', $user->getNom());
        $this->assertEquals('Jean', $user->getPrenom());
        $this->assertTrue($user->getStatut());
    }

    /**
     * Test creating utilisateur with invalid role
     */
    public function testCreateUtilisateurWithInvalidRole(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->utilisateurManager->createUtilisateur(
            'Dupont',
            'Jean',
            'jean@example.com',
            'invalid_role',
            'SecurePassword123'
        );
    }

    /**
     * Test password hashing
     */
    public function testPasswordHashing(): void
    {
        $user = $this->utilisateurManager->createUtilisateur(
            'Dupont',
            'Jean',
            'jean@example.com',
            'agriculteur',
            'SecurePassword123'
        );

        $this->assertTrue($this->utilisateurManager->isPasswordValid($user, 'SecurePassword123'));
        $this->assertFalse($this->utilisateurManager->isPasswordValid($user, 'WrongPassword'));
    }

    /**
     * Test change password
     */
    public function testChangePassword(): void
    {
        $user = $this->utilisateurManager->createUtilisateur(
            'Dupont',
            'Jean',
            'jean@example.com',
            'agriculteur',
            'OldPassword123'
        );

        $user = $this->utilisateurManager->changePassword($user, 'NewPassword456');

        $this->assertTrue($this->utilisateurManager->isPasswordValid($user, 'NewPassword456'));
        $this->assertFalse($this->utilisateurManager->isPasswordValid($user, 'OldPassword123'));
    }

    /**
     * Test get full name
     */
    public function testGetFullName(): void
    {
        $user = new Utilisateur();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');

        $fullName = $this->utilisateurManager->getFullName($user);

        $this->assertEquals('Jean Dupont', $fullName);
    }

    /**
     * Test get Symfony roles
     */
    public function testGetSymfonyRoles(): void
    {
        $user = new Utilisateur();
        $user->setRole('admin');

        $roles = $this->utilisateurManager->getSymfonyRoles($user);

        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    /**
     * Test has role
     */
    public function testHasRole(): void
    {
        $user = new Utilisateur();
        $user->setRole('admin');

        $this->assertTrue($this->utilisateurManager->hasRole($user, 'ROLE_ADMIN'));
        $this->assertFalse($this->utilisateurManager->hasRole($user, 'ROLE_STOCK'));
    }

    /**
     * Test is email valid
     */
    public function testIsEmailValid(): void
    {
        $this->assertTrue($this->utilisateurManager->isEmailValid('test@example.com'));
        $this->assertFalse($this->utilisateurManager->isEmailValid('invalid-email'));
    }

    /**
     * Test is password strong
     */
    public function testIsPasswordStrong(): void
    {
        $this->assertTrue($this->utilisateurManager->isPasswordStrong('SecurePassword123'));
        $this->assertFalse($this->utilisateurManager->isPasswordStrong('weak'));
        $this->assertFalse($this->utilisateurManager->isPasswordStrong('nouppercase123'));
        $this->assertFalse($this->utilisateurManager->isPasswordStrong('NOLOWERCASE123'));
        $this->assertFalse($this->utilisateurManager->isPasswordStrong('NoNumbers'));
    }

    /**
     * Test activate utilisateur
     */
    public function testActivateUtilisateur(): void
    {
        $user = new Utilisateur();
        $user->setStatut(false);

        $user = $this->utilisateurManager->activate($user);

        $this->assertTrue($user->getStatut());
    }

    /**
     * Test deactivate utilisateur
     */
    public function testDeactivateUtilisateur(): void
    {
        $user = new Utilisateur();
        $user->setStatut(true);

        $user = $this->utilisateurManager->deactivate($user);

        $this->assertFalse($user->getStatut());
    }

    /**
     * Test is active
     */
    public function testIsActive(): void
    {
        $user = new Utilisateur();
        $user->setStatut(true);

        $this->assertTrue($this->utilisateurManager->isActive($user));

        $user->setStatut(false);

        $this->assertFalse($this->utilisateurManager->isActive($user));
    }

    /**
     * Test get age
     */
    public function testGetAge(): void
    {
        $user = new Utilisateur();
        $user->setDateNaissance(new \DateTime('-25 years'));

        $age = $this->utilisateurManager->getAge($user);

        $this->assertGreaterThanOrEqual(24, $age);
        $this->assertLessThanOrEqual(25, $age);
    }

    /**
     * Test validate utilisateur
     */
    public function testValidateUtilisateur(): void
    {
        $user = new Utilisateur();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean@example.com');
        $user->setRole('agriculteur');
        $user->setMotDePasse('password');
        $user->setStatut(true);
        $user->setDateCreation(new \DateTime());

        $violations = $this->utilisateurManager->validate($user);

        $this->assertCount(0, $violations);
    }

    /**
     * Test create utilisateur with invalid email
     */
    public function testCreateUtilisateurWithInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->utilisateurManager->createUtilisateur(
            'Dupont',
            'Jean',
            'invalid-email',
            'agriculteur',
            'SecurePassword123'
        );
    }
}
