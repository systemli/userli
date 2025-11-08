<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RecoveryControllerTest extends WebTestCase
{
    public function testVisitRecoveryUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/recovery');

        $this->assertResponseIsSuccessful();
    }

    public function testVisitRecoveryWitInvalidRecoveryToken(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/recovery');

        $form = $crawler->selectButton('Recover')->form();
        $form['recovery_process[email]'] = 'user@example.com';
        $form['recovery_process[recoveryToken]'] = 'invalid-token';

        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
    }
}
