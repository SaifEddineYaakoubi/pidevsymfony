<?php

namespace App\Tests\Service;

use App\Entity\Stock;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Service\StockManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StockManagerTest extends KernelTestCase
{
    private StockManager $stockManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $validator = self::getContainer()->get('validator');
        $this->stockManager = new StockManager($validator);
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
     * Test creating a valid stock
     */
    public function testCreateValidStock(): void
    {
        $produit = $this->createTestProduit();

        $stock = $this->stockManager->createStock(
            '50.00',
            new \DateTime('2024-01-01'),
            new \DateTime('2025-01-01'),
            $produit
        );

        $this->assertInstanceOf(Stock::class, $stock);
        $this->assertEquals('50.00', $stock->getQuantite());
    }

    /**
     * Test creating stock with negative quantite
     */
    public function testCreateStockWithNegativeQuantite(): void
    {
        $produit = $this->createTestProduit();

        $this->expectException(\InvalidArgumentException::class);

        $this->stockManager->createStock(
            '-10.00',
            new \DateTime('2024-01-01'),
            new \DateTime('2025-01-01'),
            $produit
        );
    }

    /**
     * Test creating stock with invalid dates
     */
    public function testCreateStockWithInvalidDates(): void
    {
        $produit = $this->createTestProduit();

        $this->expectException(\InvalidArgumentException::class);

        $this->stockManager->createStock(
            '50.00',
            new \DateTime('2025-01-01'),
            new \DateTime('2024-01-01'),
            $produit
        );
    }

    /**
     * Test validate quantite
     */
    public function testValidateQuantite(): void
    {
        $this->assertTrue($this->stockManager->validateQuantite('50.00'));
        $this->assertTrue($this->stockManager->validateQuantite('0.00'));
    }

    /**
     * Test validate quantite negative
     */
    public function testValidateQuantiteNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->stockManager->validateQuantite('-10.00');
    }

    /**
     * Test validate dates
     */
    public function testValidateDates(): void
    {
        $this->assertTrue($this->stockManager->validateDates(
            new \DateTime('2024-01-01'),
            new \DateTime('2025-01-01')
        ));
    }

    /**
     * Test is expired
     */
    public function testIsExpired(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree(new \DateTime('-365 days'));
        $stock->setDateExpiration(new \DateTime('-10 days'));
        $stock->setIdProduit($produit);

        $this->assertTrue($this->stockManager->isExpired($stock));
    }

    /**
     * Test is not expired
     */
    public function testIsNotExpired(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('+365 days'));
        $stock->setIdProduit($produit);

        $this->assertFalse($this->stockManager->isExpired($stock));
    }

    /**
     * Test is expiring soon
     */
    public function testIsExpiringSoon(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree(new \DateTime('-30 days'));
        $stock->setDateExpiration(new \DateTime('+15 days'));
        $stock->setIdProduit($produit);

        $this->assertTrue($this->stockManager->isExpiringsoon($stock));
    }

    /**
     * Test get days before expiration
     */
    public function testGetDaysBeforeExpiration(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('+30 days'));
        $stock->setIdProduit($produit);

        $days = $this->stockManager->getDaysBeforeExpiration($stock);

        $this->assertGreaterThanOrEqual(29, $days);
        $this->assertLessThanOrEqual(30, $days);
    }

    /**
     * Test increase quantite
     */
    public function testIncreaseQuantite(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('2025-01-01'));
        $stock->setIdProduit($produit);

        $stock = $this->stockManager->increaseQuantite($stock, '25.00');

        $this->assertEquals('75', $stock->getQuantite());
    }

    /**
     * Test decrease quantite
     */
    public function testDecreaseQuantite(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('2025-01-01'));
        $stock->setIdProduit($produit);

        $stock = $this->stockManager->decreaseQuantite($stock, '25.00');

        $this->assertEquals('25', $stock->getQuantite());
    }

    /**
     * Test decrease quantite insufficient
     */
    public function testDecreaseQuantiteInsufficient(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('2025-01-01'));
        $stock->setIdProduit($produit);

        $this->expectException(\InvalidArgumentException::class);

        $this->stockManager->decreaseQuantite($stock, '100.00');
    }

    /**
     * Test is empty
     */
    public function testIsEmpty(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('0.00');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('2025-01-01'));
        $stock->setIdProduit($produit);

        $this->assertTrue($this->stockManager->isEmpty($stock));
    }

    /**
     * Test is low
     */
    public function testIsLow(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('5.00');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('2025-01-01'));
        $stock->setIdProduit($produit);

        $this->assertTrue($this->stockManager->isLow($stock));
    }

    /**
     * Test get status
     */
    public function testGetStatus(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('+365 days'));
        $stock->setIdProduit($produit);

        $status = $this->stockManager->getStatus($stock);

        $this->assertEquals('ok', $status);
    }

    /**
     * Test get status label
     */
    public function testGetStatusLabel(): void
    {
        $this->assertEquals('Expiré', $this->stockManager->getStatusLabel('expired'));
        $this->assertEquals('Expire bientôt', $this->stockManager->getStatusLabel('expiring_soon'));
        $this->assertEquals('Stock faible', $this->stockManager->getStatusLabel('low'));
        $this->assertEquals('Vide', $this->stockManager->getStatusLabel('empty'));
        $this->assertEquals('OK', $this->stockManager->getStatusLabel('ok'));
    }

    /**
     * Test get status color
     */
    public function testGetStatusColor(): void
    {
        $this->assertEquals('danger', $this->stockManager->getStatusColor('expired'));
        $this->assertEquals('warning', $this->stockManager->getStatusColor('expiring_soon'));
        $this->assertEquals('warning', $this->stockManager->getStatusColor('low'));
        $this->assertEquals('danger', $this->stockManager->getStatusColor('empty'));
        $this->assertEquals('success', $this->stockManager->getStatusColor('ok'));
    }

    /**
     * Test validate stock
     */
    public function testValidateStock(): void
    {
        $produit = $this->createTestProduit();

        $stock = new Stock();
        $stock->setQuantite('50.00');
        $stock->setDateEntree(new \DateTime('2024-01-01'));
        $stock->setDateExpiration(new \DateTime('2025-01-01'));
        $stock->setIdProduit($produit);

        $violations = $this->stockManager->validate($stock);

        $this->assertCount(0, $violations);
    }
}
