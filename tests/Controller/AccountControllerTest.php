<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccountControllerTest extends WebTestCase
{
    public function testVisitingUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/account');

        $this->assertResponseRedirects('/login');
    }

    public function testVisitingAuthenticated(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $client->request('GET', '/account');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Account settings');
    }

    public function testVisitingStartAsSpammer()
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'spam@example.org']);

        $client->loginUser($user);

        $client->request('GET', '/account');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testChangePassword()
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $crawler = $client->request('GET', '/account/password');

        $form = $crawler->selectButton('Submit')->form();

        $form['password[password]'] = 'password';
        $form['password[newPassword][first]'] = 'zr8cxfeeY9Qv5AR7tydM';
        $form['password[newPassword][second]'] = 'zr8cxfeeY9Qv5AR7tydM';

        $client->submit($form);

        $this->assertResponseRedirects('/account');
    }

    public function testChangePasswordIdentical()
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $crawler = $client->request('GET', '/account/password');

        $form = $crawler->selectButton('Submit')->form();

        $form['password[password]'] = 'zr8cxfeeY9Qv5AR7tydM';
        $form['password[newPassword][first]'] = 'zr8cxfeeY9Qv5AR7tydM';
        $form['password[newPassword][second]'] = 'zr8cxfeeY9Qv5AR7tydM';

        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testChangePasswordInsecure()
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $crawler = $client->request('GET', '/account/password');

        $form = $crawler->selectButton('Submit')->form();

        $form['password[password]'] = 'zr8cxfeeY9Qv5AR7tydM';
        $form['password[newPassword][first]'] = 'password';
        $form['password[newPassword][second]'] = 'password';

        $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
    }
}
