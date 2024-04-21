<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AliasControllerTest extends WebTestCase
{
    public function testVisitingUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/alias');

        $this->assertResponseRedirects('/login');
    }

    public function testVisitingAuthenticated(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $client->request('GET', '/alias');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Your custom alias addresses');
    }

    public function testVisitingStartAsSpammer()
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'spam@example.org']);

        $client->loginUser($user);

        $client->request('GET', '/alias');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateCustomAlias()
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $crawler = $client->request('GET', '/alias');

        $form = $crawler->selectButton('Add')->form();

        $form['create_custom_alias[alias]'] = 'test' . random_int(1, 1000);

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success', 'Your new alias address was created');
    }

    public function testCreateRandomAlias()
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $crawler = $client->request('GET', '/alias');

        $form = $crawler->selectButton('Generate random alias address')->form();

        $client->submit($form);

        $this->assertResponseRedirects('/alias');
    }
}
