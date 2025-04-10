<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostfixControllerTest extends WebTestCase
{
    public function testGetAliasUsersWrongApiToken(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer wrong',
        ]);
        $client->request('GET', '/api/postfix/alias/alias@example.org');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetAliasUsers(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer postfix',
        ]);

        $client->request('GET', '/api/postfix/alias/alias@example.org');
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('["user2@example.org"]', $client->getResponse()->getContent());

        $client->request('GET', '/api/postfix/alias/user@example.org');
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('[]', $client->getResponse()->getContent());
    }

    public function testGetDomainWrongApiToken(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer wrong',
        ]);
        $client->request('GET', '/api/postfix/domain/example.org');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetDomain(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer postfix',
        ]);
        $client->request('GET', '/api/postfix/domain/example.org');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('true', $client->getResponse()->getContent());
    }

    public function testGetMailboxWrongApiToken(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer wrong',
        ]);
        $client->request('GET', '/api/postfix/mailbox/user@example.org');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetMailbox(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer postfix',
        ]);
        $client->request('GET', '/api/postfix/mailbox/user@example.org');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('true', $client->getResponse()->getContent());
    }

    public function testGetSendersWrongApiToken(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer wrong',
        ]);
        $client->request('GET', '/api/postfix/senders/user@example.org');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetSenders(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer postfix',
        ]);
        $client->request('GET', '/api/postfix/senders/user@example.org');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('["user@example.org"]', $client->getResponse()->getContent());

        $client->request('GET', '/api/postfix/senders/user2@example.org');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('["user2@example.org"]', $client->getResponse()->getContent());
    }
}
