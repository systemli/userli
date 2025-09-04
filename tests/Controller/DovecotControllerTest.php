<?php

namespace App\Tests\Controller;

use App\DataFixtures\LoadApiTokenData;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DovecotControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    public function setUp(): void
    {
        $this->client = static::createClient(server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . LoadApiTokenData::DOVECOT_TOKEN_PLAIN,
            'ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ]);
    }

    public function testStatus(): void
    {

        $this->client->request('GET', '/api/dovecot/status');

        self::assertResponseStatusCodeSame(200);
    }

    public function testStatusWrongApiToken(): void
    {
        $this->client->request(method:'GET',uri: '/api/dovecot/status', server: [
            'HTTP_AUTHORIZATION' => 'Bearer wrongtoken'
        ]);

        self::assertResponseStatusCodeSame(401);
    }

    public function testPassdbUser(): void
    {
        $this->client->request('POST', '/api/dovecot/support@example.org', ['password' => 'password']);

        self::assertResponseStatusCodeSame(200);
    }

    public function testPassdbUserWrongPassword(): void
    {
        $this->client->request('POST', '/api/dovecot/support@example.org', ['password' => 'wrong']);

        self::assertResponseStatusCodeSame(401);
    }

    public function testPassdbNonexistentUser(): void
    {
        $this->client->request('POST', '/api/dovecot/nonexistent@example.org', ['password' => 'password']);

        self::assertResponseStatusCodeSame(404);
    }

    public function testPassdbSpamUser(): void
    {
        $this->client->request('POST', '/api/dovecot/spam@example.org', ['password' => 'password']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testPassdbMailCrypt(): void
    {
        $this->client->request('POST', '/api/dovecot/mailcrypt@example.org', ['password' => 'password']);

        self::assertResponseStatusCodeSame(200);
        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNotNull($data['body']['mailCryptPrivateKey']);
        self::assertNotEquals($data['body']['mailCryptPrivateKey'], "");
    }

    public function testUserdbUser(): void
    {
        $this->client->request('GET', '/api/dovecot/user@example.org');

        self::assertResponseStatusCodeSame(200);

        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($data['message'], 'success');
        self::assertEquals($data['body']['user'], 'user@example.org');
        self::assertEquals($data['body']['mailCrypt'], 0);
        self::assertEquals($data['body']['mailCryptPublicKey'], "");
        self::assertIsInt($data['body']['gid']);
        self::assertIsInt($data['body']['uid']);
        self::assertNotEquals($data['body']['home'], '');
    }

    public function testUserdbMailcrypt(): void
    {
        $this->client->request('GET', '/api/dovecot/mailcrypt@example.org');

        self::assertResponseStatusCodeSame(200);

        $data = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($data['message'], 'success');
        self::assertEquals($data['body']['user'], 'mailcrypt@example.org');
        self::assertNotEquals($data['body']['home'], '');
        self::assertEquals($data['body']['mailCrypt'], 2);
        self::assertNotNull($data['body']['mailCryptPublicKey']);
    }

    public function testUserdbNonexistentUser(): void
    {
        $this->client->request('GET', '/api/dovecot/nonexistent@example.org');

        self::assertResponseStatusCodeSame(404);
    }

    // Exclude spam users from login, but not from lookup
    public function testUserdbSpamUser(): void
    {
        $this->client->request('GET', '/api/dovecot/spam@example.org');

        self::assertResponseStatusCodeSame(200);
    }
}
