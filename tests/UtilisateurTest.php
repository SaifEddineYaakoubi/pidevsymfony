<?php

namespace App\Tests;

use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UtilisateurTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    /**
     * Test creating a valid Utilisateur
     */
    public function testValidUtilisateurCreation(): void
    {
        $user = new Utilisateur();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean.dupont@example.com');
        $user->setRole('agriculteur');
        $user->setMotDePasse('hashed_password');
        $user->setStatut(true);
        $user->setDateCreation(new \DateTime());

        $violations = $this->validator->validate($user);
        $this->assertCount(0, $violations, 'Valid utilisateur should have no validation errors');
    }

    /**
     * Test that nom is mandatory
     */
    public function testNomIsMandatory(): void
    {
        $user = new Utilisateur();
        $user->setNom('');
        $user->setPrenom('Jean');
        $user->setEmail('jean@example.com');
        $user->setRole('agriculteur');
        $user->setMotDePasse('password');
        $user->setStatut(true);
        $user->setDateCreation(new \DateTime());

        $violations = $this->validator->validate($user);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that nom has minimum length
     */
    public function testNomMinimumLength(): void
    {
        $user = new Utilisateur();
        $user->setNom('A');
        $user->setPrenom('Jean');
        $user->setEmail('jean@example.com');
        $user->setRole('agriculteur');
        $user->setMotDePasse('password');
        $user->setStatut(true);
        $user->setDateCreation(new \DateTime());

        $violations = $this->validator->validate($user);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that prenom is mandatory
     */
    public function testPrenomIsMandatory(): void
    {
        $user = new Utilisateur();
        $user->setNom('Dupont');
        $user->setPrenom('');
        $user->setEmail('jean@example.com');
        $user->setRole('agriculteur');
        $user->setMotDePasse('password');
        $user->setStatut(true);
        $user->setDateCreation(new \DateTime());

        $violations = $this->validator->validate($user);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that email is mandatory
     */
    public function testEmailIsMandatory(): void
    {
        $user = new Utilisateur();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('');
        $user->setRole('agriculteur');
        $user->setMotDePasse('password');
        $user->setStatut(true);
        $user->setDateCreation(new \DateTime());

        $violations = $this->validator->validate($user);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that email must be valid
     */
    public function testEmailMustBeValid(): void
    {
        $user = new Utilisateur();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('invalid-email');
        $user->setRole('agriculteur');
        $user->setMotDePasse('password');
        $user->setStatut(true);
        $user->setDateCreation(new \DateTime());

        $violations = $this->validator->validate($user);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that role is mandatory
     */
    public function testRoleIsMandatory(): void
    {
        $user = new Utilisateur();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean@example.com');
        $user->setRole('');
        $user->setMotDePasse('password');
        $user->setStatut(true);
        $user->setDateCreation(new \DateTime());

        $violations = $this->validator->validate($user);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that role must be valid
     */
    public function testRoleMustBeValid(): void
    {
        $user = new Utilisateur();
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setEmail('jean@example.com');
        $user->setRole('invalid_role');
        $user->setMotDePasse('password');
        $user->setStatut(true);
        $user->setDateCreation(new \DateTime());

        $violations = $this->validator->validate($user);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that role accepts valid values
     */
    public function testRoleValidValues(): void
    {
        $validRoles = ['admin', 'responsable_stock', 'agriculteur'];

        foreach ($validRoles as $role) {
            $user = new Utilisateur();
            $user->setNom('Dupont');
            $user->setPrenom('Jean');
            $user->setEmail('jean@example.com');
            $user->setRole($role);
            $user->setMotDePasse('password');
            $user->setStatut(true);
            $user->setDateCreation(new \DateTime());

            $violations = $this->validator->validate($user);
            $this->assertCount(0, $violations, "Role '$role' should be valid");
        }
    }

    /**
     * Test getUserIdentifier returns email
     */
    public function testGetUserIdentifier(): void
    {
        $user = new Utilisateur();
        $user->setEmail('jean@example.com');

        $this->assertEquals('jean@example.com', $user->getUserIdentifier());
    }

    /**
     * Test getRoles returns correct roles
     */
    public function testGetRoles(): void
    {
        $user = new Utilisateur();
        $user->setRole('admin');

        $roles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    /**
     * Test getAge calculation
     */
    public function testGetAge(): void
    {
        $user = new Utilisateur();
        $user->setDateNaissance(new \DateTime('-25 years'));

        $age = $user->getAge();
        $this->assertGreaterThanOrEqual(24, $age);
        $this->assertLessThanOrEqual(25, $age);
    }

    /**
     * Test getAge returns null when no birth date
     */
    public function testGetAgeReturnsNull(): void
    {
        $user = new Utilisateur();

        $age = $user->getAge();
        $this->assertNull($age);
    }

    /**
     * Test parcelles collection
     */
    public function testParcelles(): void
    {
        $user = new Utilisateur();
        $parcelles = $user->getParcelles();

        $this->assertCount(0, $parcelles);
    }

    /**
     * Test ventes collection
     */
    public function testVentes(): void
    {
        $user = new Utilisateur();
        $ventes = $user->getVentes();

        $this->assertCount(0, $ventes);
    }

    /**
     * Test face descriptor
     */
    public function testFaceDescriptor(): void
    {
        $user = new Utilisateur();
        $descriptor = 'face_descriptor_data';
        $user->setFaceDescriptor($descriptor);

        $this->assertEquals($descriptor, $user->getFaceDescriptor());
    }

    /**
     * Test face enabled
     */
    public function testFaceEnabled(): void
    {
        $user = new Utilisateur();
        $user->setFaceEnabled(true);

        $this->assertTrue($user->isFaceEnabled());
    }

    /**
     * Test profile picture
     */
    public function testProfilePicture(): void
    {
        $user = new Utilisateur();
        $picture = '/path/to/picture.jpg';
        $user->setProfilePicture($picture);

        $this->assertEquals($picture, $user->getProfilePicture());
    }

    /**
     * Test sexe
     */
    public function testSexe(): void
    {
        $user = new Utilisateur();
        $user->setSexe('M');

        $this->assertEquals('M', $user->getSexe());
    }
}
