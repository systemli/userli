<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class KeycloakControllerTest extends WebTestCase
{
    public function testGetUsersSearchWrongApiToken(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer wrong',
        ]);
        $client->request('GET', '/api/keycloak/example.org?search=example&max=2');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetUsersSearch(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer keycloak',
        ]);
        $client->request('GET', '/api/keycloak/example.org?search=example&max=2');

        self::assertResponseIsSuccessful();

        $expected = [
            ['id' => 'admin', 'email' => 'admin@example.org'],
            ['id' => 'user', 'email' => 'user@example.org']
        ];
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($expected, $data);
    }

    public function testGetUsersSearchNonexistentDomain(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer keycloak',
        ]);
        $client->request('GET', '/api/keycloak/nonexistent.org?search=example&max=2');

        self::assertResponseStatusCodeSame(404);
    }

    public function testGetUsersCount(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer keycloak',
        ]);
        $client->request('GET', '/api/keycloak/example.org/count');

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals(8, $data);
    }

    public function testGetOneUser(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer keycloak',
        ]);
        $client->request('GET', '/api/keycloak/example.org/user/user@example.org');

        self::assertResponseIsSuccessful();

        $expected = ['id' => 'user', 'email' => 'user@example.org'];
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($expected, $data);
    }

    public function testGetOneNonexistentUser(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer keycloak',
        ]);
        $client->request('GET', '/api/keycloak/example.org/user/nonexistent@example.org');

        self::assertResponseStatusCodeSame(404);
    }

    public function testPostUserValidate(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer keycloak',
        ]);
        $client->request('POST', '/api/keycloak/example.org/validate/support@example.org', ['credentialType' => 'password', 'password' => 'password']);

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals(['message' => 'success'], $data);

        $client->request('POST', '/api/keycloak/example.org/validate/support@example.org', ['password' => 'wrong']);

        self::assertResponseStatusCodeSame(403);
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals(['message' => 'authentication failed'], $data);

        $client->request('POST', '/api/keycloak/example.org/validate/support@example.org', ['credentialType' => 'wrong', 'password' => 'password']);

        self::assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals(['message' => 'not supported'], $data);

        $client->request('POST', '/api/keycloak/example.org/validate/404@example.org', ['credentialType' => 'password', 'password' => 'password']);

        self::assertResponseStatusCodeSame(403);
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals(['message' => 'authentication failed'], $data);
    }

    public function testPostUserValidateOTP(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer keycloak',
        ]);
        $client->request('POST', '/api/keycloak/example.org/validate/support@example.org', ['credentialType' => 'otp', 'password' => '123456']);
        self::assertResponseStatusCodeSame(403);

        $client->request('POST', '/api/keycloak/example.org/validate/totp@example.org', ['credentialType' => 'otp', 'password' => '123456']);
        self::assertResponseStatusCodeSame(403);

        $user = $client->getContainer()->get('doctrine.orm.entity_manager')->getRepository(User::class)->findOneBy(['email' => 'totp@example.org']);
        $totp = $client->getContainer()->get('scheb_two_factor.security.totp_factory')->createTotpForUser($user);

        $client->request('POST', '/api/keycloak/example.org/validate/totp@example.org', ['credentialType' => 'otp', 'password' => $totp->now()]);
        self::assertResponseIsSuccessful();
    }
    public function testGetIsConfiguredFor(): void
    {
        $client = static::createClient([], [
            'HTTP_Authorization' => 'Bearer keycloak',
        ]);
        $client->request('GET', '/api/keycloak/example.org/configured/otp/support@example.org');
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', '/api/keycloak/example.org/configured/otp/totp@example.org');
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/keycloak/example.org/configured/password/support@example.org');
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/keycloak/example.org/configured/password/404@example.org');
        self::assertResponseStatusCodeSame(404);
    }
}
