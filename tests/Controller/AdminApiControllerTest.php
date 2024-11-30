<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminApiControllerTest extends WebTestCase
{
    public function testPutTouchUserWrongApiToken(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer wrong',
        ]);
        $client->request('PUT', '/api/admin/touch_user');

        self::assertResponseStatusCodeSame(401);
    }

    public function testPutTouchUserUnknownUser(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);

        $client->request('PUT', '/api/admin/touch_user', ['email' => 'nonexistant@example.org']);
        self::assertResponseStatusCodeSame(404);
    }

    public function testPutTouchUser(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);

        $client->request('PUT', '/api/admin/touch_user', ['email' => 'user@example.org']);
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('[]', $client->getResponse()->getContent());
    }

    public function testGetDeletedUsers(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);

        $client->request('GET', '/api/admin/deleted_users');
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('["deleted@example.org"]', $client->getResponse()->getContent());
    }
}
