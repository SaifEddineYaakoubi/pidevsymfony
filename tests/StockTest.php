<?php

namespace App\Tests;

use App\Entity\Stock;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class StockTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    /**
     * Create a test Produit fixture
     */
    private function createTestProduit(): Produit
    {
        $produit = new Produit();
        $produit->setNom('Engrais NPK');
        $produit->setType('Engrais');
        $produit->setUnite('kg');
        $produit->setPrixUnitaire('25.50');
        return $produit;
    }

    /**
     * Test creating a valid Stock
     */
    public function testValidStockCreation(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('2025-01-01'));
        $stock->setIdProduit($produit);

        $violations = $this->validator->validate($stock);
        $this->assertCount(0, $violations, 'Valid stock should have no validation errors');
    }

    /**
     * Test that quantite is mandatory
     */
    public function testQuantiteIsMandatory(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        // quantite not set
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('2025-01-01'));
        $stock->setIdProduit($produit);

        $violations = $this->validator->validate($stock);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that quantite must be positive or zero
     */
    public function testQuantiteMustBePositiveOrZero(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('-10.00');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('2025-01-01'));
        $stock->setIdProduit($produit);

        $violations = $this->validator->validate($stock);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that quantite can be zero
     */
    public function testQuantiteCanBeZero(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('0.00');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('2025-01-01'));
        $stock->setIdProduit($produit);

        $violations = $this->validator->validate($stock);
        $this->assertCount(0, $violations);
    }

    /**
     * Test that date_expiration must be >= date_entree
     */
    public function testDateExpirationMustBeAfterDateEntree(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree(new \DateTime('2025-01-01'));
        $stock->setDateExpiration(new \DateTime('2024-01-01'));
        $stock->setIdProduit($produit);

        $violations = $this->validator->validate($stock);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that date_expiration can equal date_entree
     */
    public function testDateExpirationCanEqualDateEntree(): void
    {
        $produit = $this->createTestProduit();

        $sameDate = new \DateTime('2024-01-01');
        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree($sameDate);
        $stock->setDateExpiration($sameDate);
        $stock->setIdProduit($produit);

        $violations = $this->validator->validate($stock);
        $this->assertCount(0, $violations);
    }

    /**
     * Test that id_produit is mandatory
     */
    public function testIdProduitIsMandatory(): void
    {
        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('2025-01-01'));
        // id_produit not set

        $violations = $this->validator->validate($stock);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test Produit relationship
     */
    public function testProduitRelationship(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setIdProduit($produit);

        $this->assertSame($produit, $stock->getIdProduit());
    }

    /**
     * Test Utilisateur relationship
     */
    public function testUtilisateurRelationship(): void
    {
        $user = new Utilisateur();
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setEmail('test@example.com');
        $user->setRole('admin');
        $user->setMotDePasse('password');
        $user->setStatut(true);
        $user->setDateCreation(new \DateTime());

        $stock = new Stock();
        $stock->setUtilisateur($user);

        $this->assertSame($user, $stock->getUtilisateur());
    }

    /**
     * Test getIdUser returns user ID
     */
    public function testGetIdUser(): void
    {
        $user = new Utilisateur();
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setEmail('test@example.com');
        $user->setRole('admin');
        $user->setMotDePasse('password');
        $user->setStatut(true);
        $user->setDateCreation(new \DateTime());

        $stock = new Stock();
        $stock->setUtilisateur($user);

        $this->assertEquals($user->getIdUser(), $stock->getIdUser());
    }

    /**
     * Test quantite with decimal values
     */
    public function testQuantiteWithDecimalValues(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('123.45');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('2025-01-01'));
        $stock->setIdProduit($produit);

        $violations = $this->validator->validate($stock);
        $this->assertCount(0, $violations);
        $this->assertEquals('123.45', $stock->getQuantite());
    }

    /**
     * Test date_entree with past date
     */
    public function testDateEntreeWithPastDate(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree(new \DateTime('-30 days'));
        $stock->setDateExpiration(new \DateTime('+30 days'));
        $stock->setIdProduit($produit);

        $violations = $this->validator->validate($stock);
        $this->assertCount(0, $violations);
    }

    /**
     * Test date_expiration with future date
     */
    public function testDateExpirationWithFutureDate(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('+365 days'));
        $stock->setIdProduit($produit);

        $violations = $this->validator->validate($stock);
        $this->assertCount(0, $violations);
    }

    /**
     * Test getId_stock
     */
    public function testGetIdStock(): void
    {
        $stock = new Stock();
        $this->assertNull($stock->getId_stock());
    }

    /**
     * Test getIdStock alias
     */
    public function testGetIdStockAlias(): void
    {
        $stock = new Stock();
        $this->assertNull($stock->getIdStock());
    }

    /**
     * Test stock with large quantite
     */
    public function testStockWithLargeQuantite(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('999999.99');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('2025-01-01'));
        $stock->setIdProduit($produit);

        $violations = $this->validator->validate($stock);
        $this->assertCount(0, $violations);
    }
}
