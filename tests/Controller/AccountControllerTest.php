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
        $this->assertSelectorTextContains('h3', 'Change your password');
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

        $crawler = $client->request('GET', '/account');

        $form = $crawler->selectButton('Submit')->form();

        $form['password_change[password]'] = 'password';
        $form['password_change[newPassword][first]'] = 'zr8cxfeeY9Qv5AR7tydM';
        $form['password_change[newPassword][second]'] = 'zr8cxfeeY9Qv5AR7tydM';

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-success', 'Your new password is now active!');
    }

    public function testChangePasswordIdentical()
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $crawler = $client->request('GET', '/account');

        $form = $crawler->selectButton('Submit')->form();

        $form['password_change[password]'] = 'zr8cxfeeY9Qv5AR7tydM';
        $form['password_change[newPassword][first]'] = 'zr8cxfeeY9Qv5AR7tydM';
        $form['password_change[newPassword][second]'] = 'zr8cxfeeY9Qv5AR7tydM';

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-danger', 'The new password is identical with the old one.');
    }

    public function testChangePasswordInsecure()
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $crawler = $client->request('GET', '/account');

        $form = $crawler->selectButton('Submit')->form();

        $form['password_change[password]'] = 'zr8cxfeeY9Qv5AR7tydM';
        $form['password_change[newPassword][first]'] = 'password';
        $form['password_change[newPassword][second]'] = 'password';

        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-danger', 'The password comply not with our security policy.');
    }
}
