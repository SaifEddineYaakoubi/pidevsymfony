<?php

namespace App\Tests;

use App\Entity\Recolte;
use App\Entity\Culture;
use App\Entity\Parcelle;
use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RecolteTest extends KernelTestCase
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
     * Test creating a valid Recolte entity with all required fields
     */
    public function testValidRecolteCreation(): void
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

        $violations = $this->validator->validate($recolte);
        $this->assertCount(0, $violations, 'Valid recolte should have no validation errors');
    }

    /**
     * Test that quantite is mandatory
     */
    public function testQuantiteIsMandatory(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        // quantite not set (0.0 by default)
        $recolte->setDate_recolte(new \DateTime('2024-05-15'));
        $recolte->setQualite('bonne');
        $recolte->setType_culture('Maïs');
        $recolte->setLocalisation('Champ Nord');
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertGreaterThan(0, $violations->count(), 'Zero quantite should fail validation');
    }

    /**
     * Test that quantite must be positive
     */
    public function testQuantiteMustBePositive(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(-50.0); // Negative quantity
        $recolte->setDate_recolte(new \DateTime('2024-05-15'));
        $recolte->setQualite('bonne');
        $recolte->setType_culture('Maïs');
        $recolte->setLocalisation('Champ Nord');
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertGreaterThan(0, $violations->count(), 'Negative quantite should fail validation');
        $this->assertStringContainsString('strictement supérieure', (string) $violations[0]->getMessage());
    }

    /**
     * Test that date_recolte is mandatory
     */
    public function testDate_recolteIsMandatory(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        // date_recolte not set (null)
        $recolte->setQualite('bonne');
        $recolte->setType_culture('Maïs');
        $recolte->setLocalisation('Champ Nord');
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertGreaterThan(0, $violations->count(), 'Null date_recolte should fail validation');
    }

    /**
     * Test that date_recolte cannot be in the future
     */
    public function testDate_recolteCannotBeInFuture(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        $recolte->setDate_recolte(new \DateTime('+30 days')); // Future date
        $recolte->setQualite('bonne');
        $recolte->setType_culture('Maïs');
        $recolte->setLocalisation('Champ Nord');
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertGreaterThan(0, $violations->count(), 'Future date_recolte should fail validation');
        $this->assertStringContainsString('futur', (string) $violations[0]->getMessage());
    }

    /**
     * Test that qualite is mandatory
     */
    public function testQualiteIsMandatory(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        $recolte->setDate_recolte(new \DateTime('2024-05-15'));
        $recolte->setQualite(''); // Empty qualite
        $recolte->setType_culture('Maïs');
        $recolte->setLocalisation('Champ Nord');
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertGreaterThan(0, $violations->count(), 'Empty qualite should fail validation');
        $this->assertStringContainsString('obligatoire', (string) $violations[0]->getMessage());
    }

    /**
     * Test that qualite must be one of the allowed values
     */
    public function testQualiteValidation(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        $recolte->setDate_recolte(new \DateTime('2024-05-15'));
        $recolte->setQualite('invalid_quality'); // Invalid quality
        $recolte->setType_culture('Maïs');
        $recolte->setLocalisation('Champ Nord');
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertGreaterThan(0, $violations->count(), 'Invalid qualite should fail validation');
        $this->assertStringContainsString('invalide', (string) $violations[0]->getMessage());
    }

    /**
     * Test that qualite accepts all valid values
     */
    public function testQualiteValidValues(): void
    {
        $validQualities = ['excellente', 'bonne', 'moyenne', 'mauvaise'];
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        foreach ($validQualities as $quality) {
            $recolte = new Recolte();
            $recolte->setQuantite(100.5);
            $recolte->setDate_recolte(new \DateTime('2024-05-15'));
            $recolte->setQualite($quality);
            $recolte->setType_culture('Maïs');
            $recolte->setLocalisation('Champ Nord');
            $recolte->setId_culture($culture);
            $recolte->setUtilisateur($user);

            $violations = $this->validator->validate($recolte);
            $this->assertCount(0, $violations, "Quality '$quality' should be valid");
        }
    }

    /**
     * Test that type_culture is mandatory
     */
    public function testType_cultureIsMandatory(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        $recolte->setDate_recolte(new \DateTime('2024-05-15'));
        $recolte->setQualite('bonne');
        $recolte->setType_culture(''); // Empty type_culture
        $recolte->setLocalisation('Champ Nord');
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertGreaterThan(0, $violations->count(), 'Empty type_culture should fail validation');
    }

    /**
     * Test that type_culture has minimum length
     */
    public function testType_cultureMinimumLength(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        $recolte->setDate_recolte(new \DateTime('2024-05-15'));
        $recolte->setQualite('bonne');
        $recolte->setType_culture('M'); // Too short (min 2)
        $recolte->setLocalisation('Champ Nord');
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertGreaterThan(0, $violations->count(), 'type_culture too short should fail validation');
        $this->assertStringContainsString('au moins', (string) $violations[0]->getMessage());
    }

    /**
     * Test that type_culture has maximum length
     */
    public function testType_cultureMaximumLength(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        $recolte->setDate_recolte(new \DateTime('2024-05-15'));
        $recolte->setQualite('bonne');
        $recolte->setType_culture(str_repeat('A', 101)); // Too long (max 100)
        $recolte->setLocalisation('Champ Nord');
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertGreaterThan(0, $violations->count(), 'type_culture too long should fail validation');
        $this->assertStringContainsString('dépasser', (string) $violations[0]->getMessage());
    }

    /**
     * Test that localisation is mandatory
     */
    public function testLocalisationIsMandatory(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        $recolte->setDate_recolte(new \DateTime('2024-05-15'));
        $recolte->setQualite('bonne');
        $recolte->setType_culture('Maïs');
        $recolte->setLocalisation(''); // Empty localisation
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertGreaterThan(0, $violations->count(), 'Empty localisation should fail validation');
    }

    /**
     * Test that localisation has minimum length
     */
    public function testLocalisationMinimumLength(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        $recolte->setDate_recolte(new \DateTime('2024-05-15'));
        $recolte->setQualite('bonne');
        $recolte->setType_culture('Maïs');
        $recolte->setLocalisation('C'); // Too short (min 2)
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertGreaterThan(0, $violations->count(), 'localisation too short should fail validation');
    }

    /**
     * Test that localisation has maximum length
     */
    public function testLocalisationMaximumLength(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        $recolte->setDate_recolte(new \DateTime('2024-05-15'));
        $recolte->setQualite('bonne');
        $recolte->setType_culture('Maïs');
        $recolte->setLocalisation(str_repeat('A', 151)); // Too long (max 150)
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertGreaterThan(0, $violations->count(), 'localisation too long should fail validation');
    }

    /**
     * Test that utilisateur is mandatory
     */
    public function testUtilisateurIsMandatory(): void
    {
        $parcelle = $this->createTestParcelle($this->createTestUtilisateur());
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        $recolte->setDate_recolte(new \DateTime('2024-05-15'));
        $recolte->setQualite('bonne');
        $recolte->setType_culture('Maïs');
        $recolte->setLocalisation('Champ Nord');
        $recolte->setId_culture($culture);
        // utilisateur not set

        $violations = $this->validator->validate($recolte);
        $this->assertGreaterThan(0, $violations->count(), 'Null utilisateur should fail validation');
    }

    /**
     * Test Culture relationship (optional)
     */
    public function testCulture_relationship(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setId_culture($culture);

        $this->assertSame($culture, $recolte->getId_culture(), 'Culture relationship should be set correctly');
    }

    /**
     * Test Utilisateur relationship
     */
    public function testUtilisateur_relationship(): void
    {
        $user = $this->createTestUtilisateur();

        $recolte = new Recolte();
        $recolte->setUtilisateur($user);

        $this->assertSame($user, $recolte->getUtilisateur(), 'Utilisateur relationship should be set correctly');
    }

    /**
     * Test getter/setter aliases for camelCase naming
     */
    public function testCamelCaseAliases(): void
    {
        $user = $this->createTestUtilisateur();
        $recolte = new Recolte();

        // Test type_culture aliases
        $recolte->setTypeCulture('Blé');
        $this->assertEquals('Blé', $recolte->getTypeCulture());
        $this->assertEquals('Blé', $recolte->getType_culture());

        // Test date_recolte aliases
        $date = new \DateTime('2024-05-15');
        $recolte->setDateRecolte($date);
        $this->assertEquals($date, $recolte->getDateRecolte());
        $this->assertEquals($date, $recolte->getDate_recolte());

        // Test id_recolte aliases
        $recolte->setIdRecolte(1);
        $this->assertEquals(1, $recolte->getIdRecolte());
        $this->assertEquals(1, $recolte->getId_recolte());

        // Test id_culture aliases
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);
        $recolte->setIdCulture($culture);
        $this->assertEquals($culture, $recolte->getIdCulture());
        $this->assertEquals($culture, $recolte->getId_culture());
    }

    /**
     * Test getId_user method returns user ID
     */
    public function testGetId_userReturnsUserId(): void
    {
        $user = $this->createTestUtilisateur();
        $recolte = new Recolte();
        $recolte->setUtilisateur($user);

        // getId_user should return the user's ID
        $this->assertEquals($user->getIdUser(), $recolte->getId_user());
    }

    /**
     * Test getIdUser method returns user ID
     */
    public function testGetIdUserReturnsUserId(): void
    {
        $user = $this->createTestUtilisateur();
        $recolte = new Recolte();
        $recolte->setUtilisateur($user);

        // getIdUser should return the user's ID
        $this->assertEquals($user->getIdUser(), $recolte->getIdUser());
    }

    /**
     * Test default values in constructor
     */
    public function testDefaultValuesInConstructor(): void
    {
        $recolte = new Recolte();

        $this->assertEquals(0.0, $recolte->getQuantite(), 'Default quantite should be 0.0');
        $this->assertNull($recolte->getDate_recolte(), 'Default date_recolte should be null');
        $this->assertEquals('', $recolte->getQualite(), 'Default qualite should be empty string');
        $this->assertNull($recolte->getId_culture(), 'Default id_culture should be null');
        $this->assertEquals('', $recolte->getType_culture(), 'Default type_culture should be empty string');
        $this->assertEquals('', $recolte->getLocalisation(), 'Default localisation should be empty string');
    }

    /**
     * Test quantite with decimal values
     */
    public function testQuantiteWithDecimalValues(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(123.456);
        $recolte->setDate_recolte(new \DateTime('2024-05-15'));
        $recolte->setQualite('bonne');
        $recolte->setType_culture('Maïs');
        $recolte->setLocalisation('Champ Nord');
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertCount(0, $violations, 'Decimal quantite should be valid');
        $this->assertEquals(123.456, $recolte->getQuantite());
    }

    /**
     * Test date_recolte with today's date
     */
    public function testDate_recolteWithTodayDate(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        $recolte->setDate_recolte(new \DateTime('today'));
        $recolte->setQualite('bonne');
        $recolte->setType_culture('Maïs');
        $recolte->setLocalisation('Champ Nord');
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertCount(0, $violations, 'Today\'s date should be valid');
    }

    /**
     * Test date_recolte with past date
     */
    public function testDate_recolteWithPastDate(): void
    {
        $user = $this->createTestUtilisateur();
        $parcelle = $this->createTestParcelle($user);
        $culture = $this->createTestCulture($parcelle);

        $recolte = new Recolte();
        $recolte->setQuantite(100.5);
        $recolte->setDate_recolte(new \DateTime('-30 days'));
        $recolte->setQualite('bonne');
        $recolte->setType_culture('Maïs');
        $recolte->setLocalisation('Champ Nord');
        $recolte->setId_culture($culture);
        $recolte->setUtilisateur($user);

        $violations = $this->validator->validate($recolte);
        $this->assertCount(0, $violations, 'Past date should be valid');
    }
}
