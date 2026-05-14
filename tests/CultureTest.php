<?php

namespace App\Tests;

use App\Entity\Culture;
use App\Entity\Parcelle;
use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CultureTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
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
     * Test creating a valid Culture entity with all required fields
     */
    public function testValidCultureCreation(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('2024-01-01'));
        $culture->setDate_recolte_prevue(new \DateTime('2024-06-01'));
        $culture->setId_parcelle($parcelle);

        $violations = $this->validator->validate($culture);
        $this->assertCount(0, $violations, 'Valid culture should have no validation errors');
    }

    /**
     * Test that type_culture is mandatory
     */
    public function testType_cultureIsMandatory(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $culture = new Culture();
        $culture->setType_culture(''); // Empty type_culture
        $culture->setDate_plantation(new \DateTime('2024-01-01'));
        $culture->setDate_recolte_prevue(new \DateTime('2024-06-01'));
        $culture->setId_parcelle($parcelle);

        $violations = $this->validator->validate($culture);
        $this->assertGreaterThan(0, $violations->count(), 'Empty type_culture should fail validation');
        $this->assertStringContainsString('obligatoire', (string) $violations[0]->getMessage());
    }

    /**
     * Test that date_plantation is mandatory
     */
    public function testDate_plantationIsMandatory(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $culture = new Culture();
        $culture->setType_culture('Maïs');
        // date_plantation not set (null)
        $culture->setDate_recolte_prevue(new \DateTime('2024-06-01'));
        $culture->setId_parcelle($parcelle);

        $violations = $this->validator->validate($culture);
        $this->assertGreaterThan(0, $violations->count(), 'Null date_plantation should fail validation');
    }

    /**
     * Test that date_recolte_prevue is mandatory
     */
    public function testDate_recolte_prevueIsMandatory(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('2024-01-01'));
        // date_recolte_prevue not set (null)
        $culture->setId_parcelle($parcelle);

        $violations = $this->validator->validate($culture);
        $this->assertGreaterThan(0, $violations->count(), 'Null date_recolte_prevue should fail validation');
    }

    /**
     * Test that etat_croissance must be one of the allowed values
     */
    public function testEtat_croissanceValidation(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('2024-01-01'));
        $culture->setDate_recolte_prevue(new \DateTime('2024-06-01'));
        $culture->setEtat_croissance('invalid_state'); // Invalid state
        $culture->setId_parcelle($parcelle);

        $violations = $this->validator->validate($culture);
        $this->assertGreaterThan(0, $violations->count(), 'Invalid etat_croissance should fail validation');
    }

    /**
     * Test that etat_croissance accepts valid values
     */
    public function testEtat_croissanceValidValues(): void
    {
        $validStates = ['germination', 'croissance', 'floraison', 'maturite'];
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        foreach ($validStates as $state) {
            $culture = new Culture();
            $culture->setType_culture('Maïs');
            $culture->setDate_plantation(new \DateTime('2024-01-01'));
            $culture->setDate_recolte_prevue(new \DateTime('2024-06-01'));
            $culture->setEtat_croissance($state);
            $culture->setId_parcelle($parcelle);

            $violations = $this->validator->validate($culture);
            $this->assertCount(0, $violations, "State '$state' should be valid");
        }
    }

    /**
     * Test that id_parcelle is mandatory
     */
    public function testId_parcelleIsMandatory(): void
    {
        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('2024-01-01'));
        $culture->setDate_recolte_prevue(new \DateTime('2024-06-01'));
        // id_parcelle not set (null)

        $violations = $this->validator->validate($culture);
        $this->assertGreaterThan(0, $violations->count(), 'Null id_parcelle should fail validation');
    }

    /**
     * Test that date_recolte_prevue must be after date_plantation
     */
    public function testDate_recolte_prevueMustBeAfterDate_plantation(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('2024-06-01'));
        $culture->setDate_recolte_prevue(new \DateTime('2024-01-01')); // Before plantation date
        $culture->setId_parcelle($parcelle);

        $violations = $this->validator->validate($culture);
        $this->assertGreaterThan(0, $violations->count(), 'date_recolte_prevue before date_plantation should fail validation');
        $this->assertStringContainsString('supérieure', (string) $violations[0]->getMessage());
    }

    /**
     * Test that date_recolte_prevue cannot equal date_plantation
     */
    public function testDate_recolte_prevueCannotEqualDate_plantation(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $sameDate = new \DateTime('2024-06-01');
        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation($sameDate);
        $culture->setDate_recolte_prevue($sameDate); // Same date
        $culture->setId_parcelle($parcelle);

        $violations = $this->validator->validate($culture);
        $this->assertGreaterThan(0, $violations->count(), 'date_recolte_prevue equal to date_plantation should fail validation');
    }

    /**
     * Test default etat_croissance value
     */
    public function testDefaultEtat_croissance(): void
    {
        $culture = new Culture();
        $this->assertEquals('germination', $culture->getEtat_croissance(), 'Default etat_croissance should be germination');
    }

    /**
     * Test automatic etat_croissance calculation based on dates
     */
    public function testAutomatic_etat_croissanceCalculation(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        // Test: plantation in future -> germination
        $culture = new Culture();
        $culture->setType_culture('Maïs');
        $culture->setDate_plantation(new \DateTime('+30 days'));
        $culture->setDate_recolte_prevue(new \DateTime('+60 days'));
        $culture->setId_parcelle($parcelle);
        $culture->updateEtatCroissanceAuto();
        $this->assertEquals('germination', $culture->getEtat_croissance(), 'Future plantation should be germination');

        // Test: plantation in past, early growth -> germination or croissance
        $culture2 = new Culture();
        $culture2->setType_culture('Maïs');
        $culture2->setDate_plantation(new \DateTime('-30 days'));
        $culture2->setDate_recolte_prevue(new \DateTime('+30 days'));
        $culture2->setId_parcelle($parcelle);
        $culture2->updateEtatCroissanceAuto();
        $this->assertContains($culture2->getEtat_croissance(), ['germination', 'croissance'], 'Early growth should be germination or croissance');
    }

    /**
     * Test Parcelle relationship
     */
    public function testParcelle_relationship(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);

        $culture = new Culture();
        $culture->setId_parcelle($parcelle);

        $this->assertSame($parcelle, $culture->getId_parcelle(), 'Parcelle relationship should be set correctly');
        $this->assertSame($parcelle, $culture->getParcelle(), 'getParcelle() alias should return the same Parcelle');
    }

    /**
     * Test Recolte collection management
     */
    public function testRecolte_collectionManagement(): void
    {
        $culture = new Culture();
        $this->assertCount(0, $culture->getRecoltes(), 'New culture should have empty recoltes collection');

        // Note: Recolte entity would need to be created and added here
        // This is a basic test to ensure the collection exists
        $recoltes = $culture->getRecoltes();
        $this->assertIsIterable($recoltes, 'Recoltes should be iterable');
    }

    /**
     * Test getter/setter aliases for camelCase naming
     */
    public function testCamelCaseAliases(): void
    {
        $culture = new Culture();
        
        // Test type_culture aliases
        $culture->setTypeCulture('Blé');
        $this->assertEquals('Blé', $culture->getTypeCulture());
        $this->assertEquals('Blé', $culture->getType_culture());

        // Test date_plantation aliases
        $date = new \DateTime('2024-01-01');
        $culture->setDatePlantation($date);
        $this->assertEquals($date, $culture->getDatePlantation());
        $this->assertEquals($date, $culture->getDate_plantation());

        // Test date_recolte_prevue aliases
        $date2 = new \DateTime('2024-06-01');
        $culture->setDateRecoltePrevue($date2);
        $this->assertEquals($date2, $culture->getDateRecoltePrevue());
        $this->assertEquals($date2, $culture->getDate_recolte_prevue());

        // Test etat_croissance aliases
        $culture->setEtatCroissance('floraison');
        $this->assertEquals('floraison', $culture->getEtatCroissance());
        $this->assertEquals('floraison', $culture->getEtat_croissance());
    }
}
