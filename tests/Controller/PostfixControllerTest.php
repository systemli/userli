<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\DataFixtures\LoadApiTokenData;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostfixControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient(server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.LoadApiTokenData::POSTFIX_TOKEN_PLAIN,
            'ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ]);
    }

    public function testGetAliasUsersWrongApiToken(): void
    {
        $this->client->request(method: 'GET', uri: '/api/postfix/alias/alias@example.org', server: [
            'HTTP_Authorization' => 'Bearer wrong',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetAliasUsers(): void
    {
        $this->client->request('GET', '/api/postfix/alias/alias@example.org');
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('["user2@example.org"]', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/api/postfix/alias/user@example.org');
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('[]', $this->client->getResponse()->getContent());
    }

    public function testGetDomainWrongApiToken(): void
    {
        $this->client->request(method: 'GET', uri: '/api/postfix/domain/example.org', server: [
            'HTTP_Authorization' => 'Bearer wrong',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetDomain(): void
    {
        $this->client->request('GET', '/api/postfix/domain/example.org');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('true', $this->client->getResponse()->getContent());
    }

    public function testGetMailboxWrongApiToken(): void
    {
        $this->client->request(method: 'GET', uri: '/api/postfix/mailbox/user@example.org', server: [
            'HTTP_Authorization' => 'Bearer wrong',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetMailbox(): void
    {
        $this->client->request('GET', '/api/postfix/mailbox/user@example.org');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('true', $this->client->getResponse()->getContent());
    }

    public function testGetSendersWrongApiToken(): void
    {
        $this->client->request(method: 'GET', uri: '/api/postfix/senders/user@example.org', server: [
            'HTTP_AUTHORIZATION' => 'Bearer wrongtoken',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetSenders(): void
    {
        $this->client->request('GET', '/api/postfix/senders/user@example.org');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('["user@example.org"]', $this->client->getResponse()->getContent());

        $this->client->request('GET', '/api/postfix/senders/user2@example.org');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('["user2@example.org"]', $this->client->getResponse()->getContent());
    }
}
