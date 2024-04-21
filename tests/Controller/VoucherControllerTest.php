<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VoucherControllerTest extends WebTestCase
{
    public function testVisitingUnauthenticated()
    {
        $client = static::createClient();
        $client->request('GET', '/voucher');

        $this->assertResponseRedirects('/login');
    }

    public function testVisitingAuthenticated()
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $client->request('GET', '/voucher');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Your invite codes');
    }

    public function testVisitingStartAsSpammer()
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'spam@example.org']);

        $client->loginUser($user);

        $client->request('GET', '/voucher');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateVoucher()
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'support@example.org']);

        $client->loginUser($user);

        $crawler = $client->request('GET', '/voucher');

        $form = $crawler->selectButton('Create invite code')->form();
        $client->submit($form);

        $this->assertResponseIsSuccessful();
    }
}
