<?php

declare(strict_types=1);

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
        $this->assertSelectorTextContains('h3', 'Custom Aliases');
    }

    public function testVisitingStartAsSpammer(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'spam@example.org']);

        $client->loginUser($user);

        $client->request('GET', '/alias');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateCustomAlias(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $client->request('POST', '/alias/create', [
            'create_custom_alias' => [
                'alias' => 'test'.random_int(1, 1000),
                'submit' => '',
            ],
        ]);

        $this->assertResponseRedirects('/alias');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testCreateRandomAlias(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $client->request('POST', '/alias/create', [
            'create_alias' => [
                'submit' => '',
            ],
        ]);

        $this->assertResponseRedirects('/alias');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testCreateRandomAliasWithNote(): void
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $user = $container->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        $note = 'Random note '.uniqid();

        $client->request('POST', '/alias/create', [
            'create_alias' => [
                'note' => $note,
                'submit' => '',
            ],
        ]);

        $this->assertResponseRedirects('/alias');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();

        // Verify note appears in page (badge text)
        $this->assertSelectorTextContains('body', $note);
    }

    public function testCreateAliasUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('POST', '/alias/create');

        $this->assertResponseRedirects('/login');
    }

    public function testCreateAliasAsSpammer(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'spam@example.org']);

        $client->loginUser($user);

        $client->request('POST', '/alias/create', [
            'create_custom_alias' => [
                'alias' => 'test-alias',
                'submit' => '',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateCustomAliasWithInvalidData(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        // Test with empty alias
        $client->request('POST', '/alias/create', [
            'create_custom_alias' => [
                'alias' => '',
                'submit' => '',
            ],
        ]);

        $this->assertResponseRedirects('/alias');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testCreateAliasWithoutFormData(): void
    {
        $client = static::createClient();
        $user = $client->getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'user@example.org']);

        $client->loginUser($user);

        // Test POST without any form data
        $client->request('POST', '/alias/create');

        $this->assertResponseRedirects('/alias');
    }
}
