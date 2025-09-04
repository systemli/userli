<?php

namespace App\Tests\Controller;

use App\DataFixtures\LoadApiTokenData;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RetentionControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createClient(server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . LoadApiTokenData::RETENTION_TOKEN_PLAIN,
            'ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ]);
    }

    public function testPutTouchUserWrongApiToken(): void
    {
        $this->client->request(method: 'PUT', uri: '/api/retention/nonexistant@example.org/touch', server: [
            'HTTP_AUTHORIZATION' => 'Bearer wrongtoken'
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testPutTouchUserWithWrongScope(): void
    {
        $this->client->request(method: 'PUT', uri: '/api/retention/user@example.org/touch', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . LoadApiTokenData::DOVECOT_TOKEN_PLAIN,
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testPutTouchUserUnknownUser(): void
    {
        $this->client->request('PUT', '/api/retention/nonexistant@example.org/touch');
        self::assertResponseStatusCodeSame(404);
    }

    public function testPutTouchUserTimestampInFuture(): void
    {
        $this->client->request('PUT', '/api/retention/user@example.org/touch', [], [], [], json_encode(['timestamp' => 999999999999]));
        self::assertResponseStatusCodeSame(400);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertEquals('timestamp in future', $response['message']);
    }

    public function testPutTouchUser(): void
    {
        $this->client->request('PUT', '/api/retention/user@example.org/touch', [], [], [], json_encode(['timestamp' => 0]));
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('[]', $this->client->getResponse()->getContent());
    }

    public function testPutTouchUserWithoutTimestamp(): void
    {
        $this->client->request('PUT', '/api/retention/user@example.org/touch', [], [], [], json_encode([]));
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('[]', $this->client->getResponse()->getContent());
    }

    public function testGetDeletedUsersNonexistantDomain(): void
    {
        $this->client->request('GET', '/api/retention/nonexistant.org/users');
        self::assertResponseStatusCodeSame(404);
    }

    public function testGetDeletedUsersWithWrongScope(): void
    {
        $this->client->request(method: 'GET', uri: '/api/retention/example.org/users', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . LoadApiTokenData::DOVECOT_TOKEN_PLAIN,
        ]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testGetDeletedUsers(): void
    {
        $this->client->request('GET', '/api/retention/example.org/users');
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('["deleted@example.org"]', $this->client->getResponse()->getContent());
    }
}
