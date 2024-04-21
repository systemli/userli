<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OpenPGPControllerTest extends WebTestCase
{
    public function testVisitingUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/openpgp');

        $this->assertResponseRedirects('/login');
    }

    public function testVisitingAuthenticated(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $client->request('GET', '/openpgp');

        $this->assertResponseIsSuccessful();
    }
}
