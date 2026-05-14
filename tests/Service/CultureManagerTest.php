<?php

namespace App\Tests\Service;

use App\Entity\Culture;
use App\Entity\Parcelle;
use App\Entity\Utilisateur;
use App\Service\CultureManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CultureManagerTest extends KernelTestCase
{
    private CultureManager $cultureManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $validator = self::getContainer()->get('validator');
        $this->cultureManager = new CultureManager($validator);
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
     * Test: Créer une culture valide avec le service
     */
    public function testCreateValidCulture(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $culture = $this->cultureManager->createCulture(
            'Maïs',
            new \DateTime('2024-01-01'),
            new \DateTime('2024-06-01'),
            $parcelle
        );

        $this->assertInstanceOf(Culture::class, $culture);
        $this->assertEquals('Maïs', $culture->getType_culture());
        $this->assertNotNull($culture->getEtat_croissance());
    }

    /**
     * Test: Créer une culture avec dates invalides
     */
    public function testCreateCultureWithInvalidDates(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de récolte prévue doit être supérieure');

        $this->cultureManager->createCulture(
            'Maïs',
            new \DateTime('2024-06-01'),
            new \DateTime('2024-01-01'), // Date avant plantation
            $parcelle
        );
    }

    /**
     * Test: Créer une culture avec dates identiques
     */
    public function testCreateCultureWithSameDates(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $this->expectException(\InvalidArgumentException::class);

        $sameDate = new \DateTime('2024-06-01');
        $this->cultureManager->createCulture(
            'Maïs',
            $sameDate,
            $sameDate,
            $parcelle
        );
    }

    /**
     * Test: Valider les dates
     */
    public function testValidateDates(): void
    {
        $result = $this->cultureManager->validateDates(
            new \DateTime('2024-01-01'),
            new \DateTime('2024-06-01')
        );

        $this->assertTrue($result);
    }

    /**
     * Test: Valider les dates invalides
     */
    public function testValidateDatesInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->cultureManager->validateDates(
            new \DateTime('2024-06-01'),
            new \DateTime('2024-01-01')
        );
    }

    /**
     * Test: Calculer l'état de croissance
     */
    public function testCalculateGrowthState(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('-30 days'));
        $culture->setDate_recolte_prevue(new \DateTime('+30 days'));
        $culture->setId_parcelle($parcelle);

        $state = $this->cultureManager->calculateGrowthState($culture);

        $this->assertContains($state, ['germination', 'croissance', 'floraison', 'maturite']);
    }

    /**
     * Test: Vérifier si une culture est en retard
     */
    public function testIsDelayedCulture(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        // Culture avec date de récolte dans le passé
        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('-60 days'));
        $culture->setDate_recolte_prevue(new \DateTime('-10 days'));
        $culture->setEtat_croissance('croissance'); // Pas encore mature
        $culture->setId_parcelle($parcelle);

        $isDelayed = $this->cultureManager->isDelayed($culture);

        $this->assertTrue($isDelayed);
    }

    /**
     * Test: Vérifier qu'une culture n'est pas en retard
     */
    public function testIsNotDelayedCulture(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        // Culture avec date de récolte dans le futur
        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('-10 days'));
        $culture->setDate_recolte_prevue(new \DateTime('+30 days'));
        $culture->setId_parcelle($parcelle);

        $isDelayed = $this->cultureManager->isDelayed($culture);

        $this->assertFalse($isDelayed);
    }

    /**
     * Test: Obtenir le pourcentage de progression
     */
    public function testGetProgressPercentage(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('-30 days'));
        $culture->setDate_recolte_prevue(new \DateTime('+30 days'));
        $culture->setId_parcelle($parcelle);

        $progress = $this->cultureManager->getProgressPercentage($culture);

        $this->assertGreaterThanOrEqual(0, $progress);
        $this->assertLessThanOrEqual(100, $progress);
    }

    /**
     * Test: Progression à 0% (plantation dans le futur)
     */
    public function testProgressPercentageZero(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('+30 days'));
        $culture->setDate_recolte_prevue(new \DateTime('+60 days'));
        $culture->setId_parcelle($parcelle);

        $progress = $this->cultureManager->getProgressPercentage($culture);

        $this->assertEquals(0.0, $progress);
    }

    /**
     * Test: Progression à 100% (récolte terminée)
     */
    public function testProgressPercentageHundred(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('-60 days'));
        $culture->setDate_recolte_prevue(new \DateTime('-10 days'));
        $culture->setId_parcelle($parcelle);

        $progress = $this->cultureManager->getProgressPercentage($culture);

        $this->assertEquals(100.0, $progress);
    }

    /**
     * Test: Valider une culture valide
     */
    public function testValidateValidCulture(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('2024-01-01'));
        $culture->setDate_recolte_prevue(new \DateTime('2024-06-01'));
        $culture->setId_parcelle($parcelle);

        $violations = $this->cultureManager->validate($culture);

        $this->assertCount(0, $violations);
    }

    /**
     * Test: Valider une culture invalide
     */
    public function testValidateInvalidCulture(): void
    {
        $culture = new Culture();
        $culture->setType_culture('');
        // Autres champs non définis

        $violations = $this->cultureManager->validate($culture);

        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test: Créer une culture avec type vide
     */
    public function testCreateCultureWithEmptyType(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $this->expectException(\InvalidArgumentException::class);

        $this->cultureManager->createCulture(
            '',
            new \DateTime('2024-01-01'),
            new \DateTime('2024-06-01'),
            $parcelle
        );
    }

    /**
     * Test: Progression intermédiaire
     */
    public function testProgressPercentageIntermediate(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        // Culture plantée il y a 15 jours, récolte prévue dans 15 jours (50% de progression)
        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('-15 days'));
        $culture->setDate_recolte_prevue(new \DateTime('+15 days'));
        $culture->setId_parcelle($parcelle);

        $progress = $this->cultureManager->getProgressPercentage($culture);

        // Devrait être proche de 50%
        $this->assertGreaterThan(40, $progress);
        $this->assertLessThan(60, $progress);
    }
}
