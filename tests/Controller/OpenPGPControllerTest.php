<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * OpenPGPController tests
 *
 * NOTE: Before running these tests, ensure fixtures are loaded:
 * bin/console doctrine:fixtures:load --group=basic --env=test --no-interaction
 */
class OpenPGPControllerTest extends WebTestCase
{
    public function testVisitingUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/account/openpgp');

        $this->assertResponseRedirects('/login');
    }

    public function testVisitingAuthenticated(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $client->request('GET', '/account/openpgp');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'OpenPGP');
    }

    public function testOpenPgpFormSubmission(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        // First, get the form
        $crawler = $client->request('GET', '/account/openpgp');
        $form = $crawler->selectButton('Publish OpenPGP key')->form();

        // Fill in some test key text (this will likely fail validation, but tests the flow)
        $form['upload_openpgp_key[keyText]'] = '-----BEGIN PGP PUBLIC KEY BLOCK-----
Version: GnuPG v1

mQENBFxxxxx...
-----END PGP PUBLIC KEY BLOCK-----';

        // Submit the form
        $client->submit($form);

        // Should redirect back to the form (either success or validation error)
        $this->assertResponseRedirects('/account/openpgp');
    }
}
