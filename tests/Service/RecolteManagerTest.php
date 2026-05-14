<?php

namespace App\Tests\Service;

use App\Entity\Recolte;
use App\Entity\Culture;
use App\Entity\Parcelle;
use App\Entity\Utilisateur;
use App\Service\RecolteManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RecolteManagerTest extends KernelTestCase
{
    private RecolteManager $recolteManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $validator = self::getContainer()->get('validator');
        $this->recolteManager = new RecolteManager($validator);
    }

    /**
     * Create a test Utilisateur fixture
     */
    private function createTestUtilisateur(): Utilisateur
    {
        $user = new Utilisateur();
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setEmail('test@example.com');
        $user->setRole('agriculteur');
        $user->setMotDePasse('password');
        $user->setStatut(true);
        $user->setDateCreation(new \DateTime());
        return $user;
    }

    /**
     * Create a test Parcelle fixture
     */
    private function createTestParcelle(Utilisateur $user): Parcelle
    {
        $parcelle = new Parcelle();
        $parcelle->setNom('Test Parcelle');
        $parcelle->setLocalisation('Test Field');
        $parcelle->setSuperficie(100.0);
        $parcelle->setEtat('active');
        $parcelle->setId_user($user);
        return $parcelle;
    }

    /**
     * Create a test Culture fixture
     */
    private function createTestCulture(Parcelle $parcelle): Culture
    {
        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('2024-01-01'));
        $culture->setDate_recolte_prevue(new \DateTime('2024-06-01'));
        $culture->setId_parcelle($parcelle);
        return $culture;
    }

    /**
     * Test: Créer une récolte valide avec le service
     */
    public function testCreateValidRecolte(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = $this->recolteManager->createRecolte(
            100.5,
            new \DateTime('2024-05-15'),
            'bonne',
            'Maïs',
            'Champ Nord',
            $user,
            $culture
        );

        $this->assertInstanceOf(Recolte::class, $recolte);
        $this->assertEquals(100.5, $recolte->getQuantite());
        $this->assertEquals('bonne', $recolte->getQualite());
    }

    /**
     * Test: Créer une récolte avec quantité négative
     */
    public function testCreateRecolteWithNegativeQuantite(): void
    {
        $user = $this->createTestUtilisateur();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('strictement supérieure à 0');

        $this->recolteManager->createRecolte(
            -50.0,
            new \DateTime('2024-05-15'),
            'bonne',
            'Maïs',
            'Champ Nord',
            $user
        );
    }

    /**
     * Test: Créer une récolte avec quantité zéro
     */
    public function testCreateRecolteWithZeroQuantite(): void
    {
        $user = $this->createTestUtilisateur();

        $this->expectException(\InvalidArgumentException::class);

        $this->recolteManager->createRecolte(
            0.0,
            new \DateTime('2024-05-15'),
            'bonne',
            'Maïs',
            'Champ Nord',
            $user
        );
    }

    /**
     * Test: Créer une récolte avec date dans le futur
     */
    public function testCreateRecolteWithFutureDate(): void
    {
        $user = $this->createTestUtilisateur();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ne peut pas être dans le futur');

        $this->recolteManager->createRecolte(
            100.5,
            new \DateTime('+30 days'),
            'bonne',
            'Maïs',
            'Champ Nord',
            $user
        );
    }

    /**
     * Test: Valider une quantité positive
     */
    public function testValidatePositiveQuantite(): void
    {
        $result = $this->recolteManager->validateQuantite(100.5);

        $this->assertTrue($result);
    }

    /**
     * Test: Valider une quantité négative
     */
    public function testValidateNegativeQuantite(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->recolteManager->validateQuantite(-50.0);
    }

    /**
     * Test: Valider une date de récolte valide
     */
    public function testValidateDateRecolteValid(): void
    {
        $result = $this->recolteManager->validateDateRecolte(new \DateTime('-10 days'));

        $this->assertTrue($result);
    }

    /**
     * Test: Valider une date de récolte dans le futur
     */
    public function testValidateDateRecolteFuture(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->recolteManager->validateDateRecolte(new \DateTime('+30 days'));
    }

    /**
     * Test: Valider une qualité valide
     */
    public function testValidateQualiteValid(): void
    {
        $validQualities = ['excellente', 'bonne', 'moyenne', 'mauvaise'];

        foreach ($validQualities as $quality) {
            $result = $this->recolteManager->validateQualite($quality);
            $this->assertTrue($result);
        }
    }

    /**
     * Test: Valider une qualité invalide
     */
    public function testValidateQualiteInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->recolteManager->validateQualite('invalid_quality');
    }

    /**
     * Test: Calculer le rendement sans culture
     */
    public function testCalculateYieldWithoutCulture(): void
    {
        $user = $this->createTestUtilisateur();

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        $recolte->setUtilisateur($user);

        $yield = $this->recolteManager->calculateYield($recolte);

        $this->assertEquals(100.5, $yield);
    }

    /**
     * Test: Calculer le rendement avec culture
     */
    public function testCalculateYieldWithCulture(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.0);
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $yield = $this->recolteManager->calculateYield($recolte);

        // 100 / 100 = 1.0
        $this->assertEquals(1.0, $yield);
    }

    /**
     * Test: Obtenir le label de qualité
     */
    public function testGetQualiteLabel(): void
    {
        $this->assertEquals('Excellente', $this->recolteManager->getQualiteLabel('excellente'));
        $this->assertEquals('Bonne', $this->recolteManager->getQualiteLabel('bonne'));
        $this->assertEquals('Moyenne', $this->recolteManager->getQualiteLabel('moyenne'));
        $this->assertEquals('Mauvaise', $this->recolteManager->getQualiteLabel('mauvaise'));
    }

    /**
     * Test: Vérifier si une récolte est de bonne qualité
     */
    public function testIsGoodQuality(): void
    {
        $user = $this->createTestUtilisateur();

        $recolte = new Recolte();
        $recolte->setQualite('bonne');
        $recolte->setUtilisateur($user);

        $this->assertTrue($this->recolteManager->isGoodQuality($recolte));
    }

    /**
     * Test: Vérifier si une récolte est de qualité excellente
     */
    public function testIsGoodQualityExcellente(): void
    {
        $user = $this->createTestUtilisateur();

        $recolte = new Recolte();
        $recolte->setQualite('excellente');
        $recolte->setUtilisateur($user);

        $this->assertTrue($this->recolteManager->isGoodQuality($recolte));
    }

    /**
     * Test: Vérifier si une récolte n'est pas de bonne qualité
     */
    public function testIsNotGoodQuality(): void
    {
        $user = $this->createTestUtilisateur();

        $recolte = new Recolte();
        $recolte->setQualite('mauvaise');
        $recolte->setUtilisateur($user);

        $this->assertFalse($this->recolteManager->isGoodQuality($recolte));
    }

    /**
     * Test: Valider une récolte valide
     */
    public function testValidateValidRecolte(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        $recolte->setDate_recolte(new \DateTime('2024-05-15'));
        $recolte->setQualite('bonne');
        $recolte->setType_culture('Maïs');
        $recolte->setLocalisation('Champ Nord');
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->recolteManager->validate($recolte);

        $this->assertCount(0, $violations);
    }

    /**
     * Test: Valider une récolte invalide
     */
    public function testValidateInvalidRecolte(): void
    {
        $recolte = new Recolte();
        $recolte->setQuantite(0.0);
        // Autres champs non définis

        $violations = $this->recolteManager->validate($recolte);

        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test: Créer une récolte sans culture (optionnel)
     */
    public function testCreateRecolteWithoutCulture(): void
    {
        $user = $this->createTestUtilisateur();

        $recolte = $this->recolteManager->createRecolte(
            100.5,
            new \DateTime('2024-05-15'),
            'bonne',
            'Maïs',
            'Champ Nord',
            $user
        );

        $this->assertInstanceOf(Recolte::class, $recolte);
        $this->assertNull($recolte->getId_culture());
    }

    /**
     * Test: Calculer le rendement avec superficie zéro
     */
    public function testCalculateYieldWithZeroSuperficie(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $parcelle->setSuperficie(0.0);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.0);
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $yield = $this->recolteManager->calculateYield($recolte);

        // Devrait retourner la quantité si superficie est 0
        $this->assertEquals(100.0, $yield);
    }

    /**
     * Test: Créer une récolte avec date d'aujourd'hui
     */
    public function testCreateRecolteWithTodayDate(): void
    {
        $user = $this->createTestUtilisateur();

        $recolte = $this->recolteManager->createRecolte(
            100.5,
            new \DateTime('today'),
            'bonne',
            'Maïs',
            'Champ Nord',
            $user
        );

        $this->assertInstanceOf(Recolte::class, $recolte);
    }
}
