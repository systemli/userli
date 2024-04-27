<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RecoveryControllerTest extends WebTestCase
{
    public function testVisitRecoveryUnauthenticated()
    {
        $client = static::createClient();
        $client->request('GET', '/recovery');

        $this->assertResponseIsSuccessful();
    }

    public function testVisitRecoveryWitInvalidRecoveryToken()
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/recovery');

        $form = $crawler->selectButton('Recover')->form();
        $form['recovery_process[email]'] = 'user@example.com';
        $form['recovery_process[recoveryToken]'] = 'invalid-token';

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-danger', "This token has an invalid format.");
    }
}
