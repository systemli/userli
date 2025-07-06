<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class VoucherControllerTest extends WebTestCase
{
    public function testVisitingUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/voucher');

        $this->assertResponseRedirects('/login');
    }

    public function testVisitingAuthenticated(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $client->request('GET', '/voucher');

        $this->assertResponseIsSuccessful();
    }

    public function testVisitingStartAsSpammer(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'spam@example.org']);

        $client->loginUser($user);

        $client->request('GET', '/voucher');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateVoucherAsMultiplier(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'support@example.org']);

        $client->loginUser($user);

        $client->request('POST', '/voucher/create', [
            'create_voucher' => [
                'submit' => '',
            ]
        ]);

        $this->assertResponseRedirects('/voucher');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testCreateVoucherUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('POST', '/voucher/create');

        $this->assertResponseRedirects('/login');
    }

    public function testCreateVoucherAsSpammer(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'spam@example.org']);

        $client->loginUser($user);

        $client->request('POST', '/voucher/create', [
            'create_voucher' => [
                'submit' => '',
            ]
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateVoucherAsRegularUser(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $client->request('POST', '/voucher/create', [
            'create_voucher' => [
                'submit' => '',
            ]
        ]);

        $this->assertResponseRedirects('/voucher');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        // Regular users shouldn't be able to create vouchers (no MULTIPLIER role)
    }

    public function testCreateVoucherWithoutFormData(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'support@example.org']);

        $client->loginUser($user);

        // Test POST without any form data
        $client->request('POST', '/voucher/create');

        $this->assertResponseRedirects('/voucher');
    }
}
