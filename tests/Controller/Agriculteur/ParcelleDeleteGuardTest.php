<?php

namespace App\Tests\Controller\Agriculteur;

use App\Entity\Culture;
use App\Entity\Parcelle;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class ParcelleDeleteGuardTest extends WebTestCase
{
    public function testDeleteIsRefusedWhenParcelleHasCultures(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        // If the test database is not created/configured, skip instead of failing hard.
        // (Useful on dev machines where APP_ENV=test DB isn't initialized yet.)
        try {
            // Any query will force Doctrine to open the connection.
            $em->getConnection()->executeQuery('SELECT 1')->fetchOne();
        } catch (DbalException $e) {
            self::markTestSkipped('Test database is not available: ' . $e->getMessage());
        }

        // Create minimal user + parcelle + culture
        $user = new Utilisateur();
        $user->setEmail('test_parcelle_delete_guard_' . bin2hex(random_bytes(6)) . '@example.com');
        $user->setRole('agriculteur');
        // Password hashing isn't needed for loginUser(); it stores a security token directly.
        $user->setMotDePasse('dummy');
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setStatut(true);
        $user->setDateCreation(new \DateTime());

        $parcelle = new Parcelle();
        $parcelle->setNom('P1');
        $parcelle->setSuperficie(1.0);
        $parcelle->setLocalisation('Loc');
        $parcelle->setEtat('active');
        $parcelle->setId_user($user);

        $culture = new Culture();
        $culture->setTypeCulture('Blé');
        $culture->setDatePlantation(new \DateTime('2026-01-01'));
        $culture->setDateRecoltePrevue(new \DateTime('2026-02-01'));
        $culture->setEtatCroissance('germination');
        $culture->setParcelle($parcelle);

        $em->persist($user);
        $em->persist($parcelle);
        $em->persist($culture);
        $em->flush();

        // Log in as this user if supported by the entity
        $client->loginUser($user);

        // Ensure there is a session in the RequestStack for SessionTokenStorage.
        /** @var SessionInterface $session */
        $session = static::getContainer()->get('session.factory')->createSession();
        $session->start();
        static::getContainer()->get(RequestStack::class)->getCurrentRequest()?->setSession($session);

        /** @var CsrfTokenManagerInterface $csrf */
        $csrf = static::getContainer()->get(CsrfTokenManagerInterface::class);

        $id = (int) $parcelle->getId_parcelle();
        self::assertGreaterThan(0, $id);

        $tokenValue = $csrf->getToken('delete_parcelle_' . $id)->getValue();

        $client->request('POST', '/agriculteur/parcelles/' . $id . '/delete', [
            '_token' => $tokenValue,
        ]);

        self::assertResponseRedirects('/agriculteur/parcelles');

        // ensure not deleted
        $em->clear();
        $stillThere = $em->getRepository(Parcelle::class)->find($id);
        self::assertNotNull($stillThere);
    }
}

