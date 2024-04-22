<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class KeycloakControllerTest extends WebTestCase
{
    public function testGetUsersSearchWrongApiToken(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer wrong',
        ]);
        $client->request('GET', '/api/keycloak?domain=example.org&search=example&max=2');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetUsersSearch(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);
        $client->request('GET', '/api/keycloak?domain=example.org&search=example&max=2');

        self::assertResponseIsSuccessful();

        $expected = [
            [ 'id' => 'admin', 'email' => 'admin@example.org' ],
            [ 'id' => 'user', 'email' => 'user@example.org']
        ];
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($expected, $data);
    }

    public function testGetUsersSearchEmptyDomain(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);
        $client->request('GET', '/api/keycloak?domain=&search=example&max=2');

        self::assertResponseStatusCodeSame(422);
    }

    public function testGetUsersCount(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);
        $client->request('GET', '/api/keycloak/count?domain=example.org');

        self::assertResponseIsSuccessful();

        $expected = 5;
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($expected, $data);
    }

    public function testGetOneUser(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);
        $client->request('GET', '/api/keycloak/user/user@example.org?domain=example.org');

        self::assertResponseIsSuccessful();

        $expected = [ 'id' => 'user', 'email' => 'user@example.org' ];
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($expected, $data);
    }

    public function testGetOneNonexistentUser(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);
        $client->request('GET', '/api/keycloak/user/nonexistent@example.org?domain=example.org');

        self::assertResponseStatusCodeSame(404);
    }

    public function testPostUserValidatae(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);
        $client->request('POST', '/api/keycloak/validate/support@example.org', [
            'domain' => 'example.org',
            'password' => 'password',
        ]);

        self::assertResponseIsSuccessful();

        $expected = ['message' => 'success'];
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($expected, $data);
    }

    public function testPostUserValidateWrongPassword(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer insecure',
        ]);
        $client->request('POST', '/api/keycloak/validate/support@example.org', [
            'domain' => 'example.org',
            'password' => 'wrong',
        ]);

        self::assertResponseStatusCodeSame(403);

        $expected = ['message' => 'authentication failed'];
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($expected, $data);
    }
}
