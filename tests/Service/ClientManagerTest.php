<?php

namespace App\Tests\Service;

use App\Entity\Client;
use App\Service\ClientManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ClientManagerTest extends KernelTestCase
{
    private ClientManager $clientManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $validator = self::getContainer()->get('validator');
        $this->clientManager = new ClientManager($validator);
    }

    /**
     * Test creating a valid client
     */
    public function testCreateValidClient(): void
    {
        $client = $this->clientManager->createClient(
            'Entreprise ABC',
            'contact@entreprise.com',
            '123 Rue de la Paix, 75000 Paris'
        );

        $this->assertInstanceOf(Client::class, $client);
        $this->assertEquals('Entreprise ABC', $client->getNom());
    }

    /**
     * Test creating client with phone number
     */
    public function testCreateClientWithPhoneNumber(): void
    {
        $client = $this->clientManager->createClient(
            'Entreprise ABC',
            '+33123456789',
            '123 Rue de la Paix'
        );

        $this->assertInstanceOf(Client::class, $client);
    }

    /**
     * Test creating client with invalid contact
     */
    public function testCreateClientWithInvalidContact(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->clientManager->createClient(
            'Entreprise ABC',
            'invalid-contact',
            '123 Rue de la Paix'
        );
    }

    /**
     * Test is valid contact
     */
    public function testIsValidContact(): void
    {
        $this->assertTrue($this->clientManager->isValidContact('test@example.com'));
        $this->assertTrue($this->clientManager->isValidContact('+33123456789'));
        $this->assertFalse($this->clientManager->isValidContact('invalid'));
    }

    /**
     * Test is email
     */
    public function testIsEmail(): void
    {
        $this->assertTrue($this->clientManager->isEmail('test@example.com'));
        $this->assertFalse($this->clientManager->isEmail('+33123456789'));
    }

    /**
     * Test is phone number
     */
    public function testIsPhoneNumber(): void
    {
        $this->assertTrue($this->clientManager->isPhoneNumber('+33123456789'));
        $this->assertFalse($this->clientManager->isPhoneNumber('test@example.com'));
    }

    /**
     * Test get contact type
     */
    public function testGetContactType(): void
    {
        $this->assertEquals('email', $this->clientManager->getContactType('test@example.com'));
        $this->assertEquals('phone', $this->clientManager->getContactType('+33123456789'));
        $this->assertEquals('unknown', $this->clientManager->getContactType('invalid'));
    }

    /**
     * Test assign badge
     */
    public function testAssignBadge(): void
    {
        $client = new Client();
        $client = $this->clientManager->assignBadge($client, 'gold');

        $this->assertEquals('gold', $client->getBadge());
    }

    /**
     * Test remove badge
     */
    public function testRemoveBadge(): void
    {
        $client = new Client();
        $client->setBadge('gold');
        $client = $this->clientManager->removeBadge($client);

        $this->assertNull($client->getBadge());
    }

    /**
     * Test has badge
     */
    public function testHasBadge(): void
    {
        $client = new Client();
        $this->assertFalse($this->clientManager->hasBadge($client));

        $client->setBadge('gold');
        $this->assertTrue($this->clientManager->hasBadge($client));
    }

    /**
     * Test get badge label
     */
    public function testGetBadgeLabel(): void
    {
        $this->assertEquals('Or', $this->clientManager->getBadgeLabel('gold'));
        $this->assertEquals('Argent', $this->clientManager->getBadgeLabel('silver'));
        $this->assertEquals('Bronze', $this->clientManager->getBadgeLabel('bronze'));
    }

    /**
     * Test is VIP
     */
    public function testIsVIP(): void
    {
        $client = new Client();
        $this->assertFalse($this->clientManager->isVIP($client));

        $client->setBadge('gold');
        $this->assertTrue($this->clientManager->isVIP($client));
    }

    /**
     * Test is valid name
     */
    public function testIsValidName(): void
    {
        $this->assertTrue($this->clientManager->isValidName('Entreprise ABC'));
        $this->assertTrue($this->clientManager->isValidName("L'Entreprise-Dupont"));
        $this->assertFalse($this->clientManager->isValidName('Entreprise@123'));
    }

    /**
     * Test is valid address
     */
    public function testIsValidAddress(): void
    {
        $this->assertTrue($this->clientManager->isValidAddress('123 Rue de la Paix'));
        $this->assertFalse($this->clientManager->isValidAddress('AB'));
        $this->assertFalse($this->clientManager->isValidAddress(str_repeat('A', 151)));
    }

    /**
     * Test get ventes count
     */
    public function testGetVentesCount(): void
    {
        $client = new Client();
        $this->assertEquals(0, $this->clientManager->getVentesCount($client));
    }

    /**
     * Test validate client
     */
    public function testValidateClient(): void
    {
        $client = new Client();
        $client->setNom('Entreprise ABC');
        $client->setContact('contact@entreprise.com');
        $client->setAdresse('123 Rue de la Paix');

        $violations = $this->clientManager->validate($client);

        $this->assertCount(0, $violations);
    }
}
