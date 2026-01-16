<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\DataFixtures\LoadApiTokenData;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use const JSON_THROW_ON_ERROR;

class KeycloakControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient(server: [
            'HTTP_AUTHORIZATION' => 'Bearer '.LoadApiTokenData::KEYCLOAK_TOKEN_PLAIN,
            'ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ]);
    }

    public function testGetUsersSearchWrongApiToken(): void
    {
        $this->client->request(method: 'GET', uri: '/api/keycloak/example.org?search=example&max=2', server: [
            'HTTP_AUTHORIZATION' => 'Bearer wrongtoken',
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetUsersSearch(): void
    {
        $this->client->request('GET', '/api/keycloak/example.org?search=example&max=2');

        self::assertResponseIsSuccessful();

        $expected = [
            ['id' => 'admin', 'email' => 'admin@example.org'],
            ['id' => 'user', 'email' => 'user@example.org'],
        ];
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($expected, $data);
    }

    public function testGetUsersSearchNonexistentDomain(): void
    {
        $this->client->request('GET', '/api/keycloak/nonexistent.org?search=example&max=2');

        self::assertResponseStatusCodeSame(404);
    }

    public function testGetUsersCount(): void
    {
        $this->client->request('GET', '/api/keycloak/example.org/count');

        self::assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals(9, $data);
    }

    public function testGetOneUser(): void
    {
        $this->client->request('GET', '/api/keycloak/example.org/user/user@example.org');

        self::assertResponseIsSuccessful();

        $expected = ['id' => 'user', 'email' => 'user@example.org'];
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($expected, $data);
    }

    public function testGetOneNonexistentUser(): void
    {
        $this->client->request('GET', '/api/keycloak/example.org/user/nonexistent@example.org');

        self::assertResponseStatusCodeSame(404);
    }

    public function testPostUserValidate(): void
    {
        $this->client->request('POST', '/api/keycloak/example.org/validate/support@example.org', ['credentialType' => 'password', 'password' => 'password']);

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals(['message' => 'success'], $data);

        $this->client->request('POST', '/api/keycloak/example.org/validate/support@example.org', ['password' => 'wrong']);

        self::assertResponseStatusCodeSame(403);
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals(['message' => 'authentication failed'], $data);

        $this->client->request('POST', '/api/keycloak/example.org/validate/support@example.org', ['credentialType' => 'wrong', 'password' => 'password']);

        self::assertResponseStatusCodeSame(400);
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals(['message' => 'not supported'], $data);

        $this->client->request('POST', '/api/keycloak/example.org/validate/404@example.org', ['credentialType' => 'password', 'password' => 'password']);

        self::assertResponseStatusCodeSame(403);
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals(['message' => 'authentication failed'], $data);
    }

    public function testPostUserValidateOTP(): void
    {
        $this->client->request('POST', '/api/keycloak/example.org/validate/support@example.org', ['credentialType' => 'otp', 'password' => '123456']);
        self::assertResponseStatusCodeSame(403);

        $this->client->request('POST', '/api/keycloak/example.org/validate/totp@example.org', ['credentialType' => 'otp', 'password' => '123456']);
        self::assertResponseStatusCodeSame(403);

        $user = $this->client->getContainer()->get('doctrine.orm.entity_manager')->getRepository(User::class)->findOneBy(['email' => 'totp@example.org']);
        $totp = $this->client->getContainer()->get('scheb_two_factor.security.totp_factory')->createTotpForUser($user);

        $this->client->request('POST', '/api/keycloak/example.org/validate/totp@example.org', ['credentialType' => 'otp', 'password' => $totp->now()]);
        self::assertResponseIsSuccessful();
    }

    public function testGetIsConfiguredFor(): void
    {
        $this->client->request('GET', '/api/keycloak/example.org/configured/otp/support@example.org');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/api/keycloak/example.org/configured/otp/totp@example.org');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/keycloak/example.org/configured/password/support@example.org');
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/keycloak/example.org/configured/password/404@example.org');
        self::assertResponseStatusCodeSame(404);
    }
}
