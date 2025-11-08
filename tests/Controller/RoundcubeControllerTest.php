<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\DataFixtures\LoadApiTokenData;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use const JSON_THROW_ON_ERROR;

class RoundcubeControllerTest extends WebTestCase
{
    public function testGetUserAliasesWrongCredentials(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/roundcube/aliases');

        self::assertResponseStatusCodeSame(401);
    }

    public function testInvalidRequestFormat(): void
    {
        $client = static::createClient([], [
            'HTTP_AUTHORIZATION' => 'Bearer '.LoadApiTokenData::ROUNDCUBE_TOKEN_PLAIN,
        ]);
        $client->request(method: 'POST', uri: '/api/roundcube/aliases', content: json_encode([
            'email' => 'user2@example.org', 'password' => 'password',
        ]));

        self::assertResponseStatusCodeSame(400);
    }

    public function testGetUserAliases(): void
    {
        $client = static::createClient([], [
            'HTTP_AUTHORIZATION' => 'Bearer '.LoadApiTokenData::ROUNDCUBE_TOKEN_PLAIN,
            'ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ]);
        $client->request(method: 'POST', uri: '/api/roundcube/aliases', content: json_encode([
            'email' => 'user2@example.org', 'password' => 'password',
        ]));

        self::assertResponseIsSuccessful();

        $expected = [
            'alias@example.org',
            'alias2@example.org',
        ];
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($expected, $data);
    }

    public function testEmptyUserAliases(): void
    {
        $client = static::createClient([], [
            'HTTP_AUTHORIZATION' => 'Bearer '.LoadApiTokenData::ROUNDCUBE_TOKEN_PLAIN,
            'ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ]);
        $client->request(method: 'POST', uri: '/api/roundcube/aliases', content: json_encode([
            'email' => 'support@example.org', 'password' => 'password',
        ]));

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals([], $data);
    }
}
