<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RetentionControllerTest extends WebTestCase
{
    public function testPutTouchUserWrongApiToken(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer wrong',
        ]);
        $client->request('PUT', '/api/retention/nonexistant@example.org/touch_user');

        self::assertResponseStatusCodeSame(401);
    }

    public function testPutTouchUserUnknownUser(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);

        $client->request('PUT', '/api/retention/nonexistant@example.org/touch_user');
        self::assertResponseStatusCodeSame(404);
    }

    public function testPutTouchUserTimestampInFuture(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);

        $client->request('PUT', '/api/retention/user@example.org/touch_user', ['timestamp' => 999999999999]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testPutTouchUser(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);

        $client->request('PUT', '/api/retention/user@example.org/touch_user', ['timestamp' => 0]);
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('[]', $client->getResponse()->getContent());
    }

    public function testGetDeletedUsersNonexistantDomain(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);

        $client->request('GET', '/api/retention/nonexistant.org/deleted_users');
        self::assertResponseStatusCodeSame(404);
    }

    public function testGetDeletedUsers(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);

        $client->request('GET', '/api/retention/example.org/deleted_users');
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('["deleted@example.org"]', $client->getResponse()->getContent());
    }
}
