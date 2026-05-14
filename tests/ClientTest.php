<?php

namespace App\Tests;

use App\Entity\Client;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ClientTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    /**
     * Test creating a valid Client
     */
    public function testValidClientCreation(): void
    {
        $client = new Client();
        $client->setNom('Entreprise ABC');
        $client->setContact('contact@entreprise.com');
        $client->setAdresse('123 Rue de la Paix, 75000 Paris');

        $violations = $this->validator->validate($client);
        $this->assertCount(0, $violations, 'Valid client should have no validation errors');
    }

    /**
     * Test that nom is mandatory
     */
    public function testNomIsMandatory(): void
    {
        $client = new Client();
        $client->setNom('');
        $client->setContact('contact@entreprise.com');
        $client->setAdresse('123 Rue de la Paix');

        $violations = $this->validator->validate($client);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that nom has minimum length
     */
    public function testNomMinimumLength(): void
    {
        $client = new Client();
        $client->setNom('AB');
        $client->setContact('contact@entreprise.com');
        $client->setAdresse('123 Rue de la Paix');

        $violations = $this->validator->validate($client);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that nom accepts only valid characters
     */
    public function testNomValidCharacters(): void
    {
        $client = new Client();
        $client->setNom('Entreprise@123');
        $client->setContact('contact@entreprise.com');
        $client->setAdresse('123 Rue de la Paix');

        $violations = $this->validator->validate($client);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that contact is mandatory
     */
    public function testContactIsMandatory(): void
    {
        $client = new Client();
        $client->setNom('Entreprise ABC');
        $client->setContact('');
        $client->setAdresse('123 Rue de la Paix');

        $violations = $this->validator->validate($client);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that contact accepts email
     */
    public function testContactWithEmail(): void
    {
        $client = new Client();
        $client->setNom('Entreprise ABC');
        $client->setContact('contact@entreprise.com');
        $client->setAdresse('123 Rue de la Paix');

        $violations = $this->validator->validate($client);
        $this->assertCount(0, $violations);
    }

    /**
     * Test that contact accepts phone number
     */
    public function testContactWithPhoneNumber(): void
    {
        $client = new Client();
        $client->setNom('Entreprise ABC');
        $client->setContact('+33123456789');
        $client->setAdresse('123 Rue de la Paix');

        $violations = $this->validator->validate($client);
        $this->assertCount(0, $violations);
    }

    /**
     * Test that contact rejects invalid format
     */
    public function testContactInvalidFormat(): void
    {
        $client = new Client();
        $client->setNom('Entreprise ABC');
        $client->setContact('invalid-contact');
        $client->setAdresse('123 Rue de la Paix');

        $violations = $this->validator->validate($client);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that adresse is mandatory
     */
    public function testAdresseIsMandatory(): void
    {
        $client = new Client();
        $client->setNom('Entreprise ABC');
        $client->setContact('contact@entreprise.com');
        $client->setAdresse('');

        $violations = $this->validator->validate($client);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test that adresse has minimum length
     */
    public function testAdresseMinimumLength(): void
    {
        $client = new Client();
        $client->setNom('Entreprise ABC');
        $client->setContact('contact@entreprise.com');
        $client->setAdresse('AB');

        $violations = $this->validator->validate($client);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test badge
     */
    public function testBadge(): void
    {
        $client = new Client();
        $client->setBadge('gold');

        $this->assertEquals('gold', $client->getBadge());
    }

    /**
     * Test id_user
     */
    public function testIdUser(): void
    {
        $client = new Client();
        $client->setId_user(123);

        $this->assertEquals(123, $client->getId_user());
    }

    /**
     * Test ventes collection
     */
    public function testVentes(): void
    {
        $client = new Client();
        $ventes = $client->getVentes();

        $this->assertCount(0, $ventes);
    }

    /**
     * Test nom with valid characters
     */
    public function testNomWithValidCharacters(): void
    {
        $client = new Client();
        $client->setNom("L'Entreprise-Dupont");
        $client->setContact('contact@entreprise.com');
        $client->setAdresse('123 Rue de la Paix');

        $violations = $this->validator->validate($client);
        $this->assertCount(0, $violations);
    }

    /**
     * Test contact with minimum length
     */
    public function testContactMinimumLength(): void
    {
        $client = new Client();
        $client->setNom('Entreprise ABC');
        $client->setContact('short');
        $client->setAdresse('123 Rue de la Paix');

        $violations = $this->validator->validate($client);
        $this->assertGreaterThan(0, $violations->count());
    }

    /**
     * Test adresse with maximum length
     */
    public function testAdresseMaximumLength(): void
    {
        $client = new Client();
        $client->setNom('Entreprise ABC');
        $client->setContact('contact@entreprise.com');
        $client->setAdresse(str_repeat('A', 151));

        $violations = $this->validator->validate($client);
        $this->assertGreaterThan(0, $violations->count());
    }
}
