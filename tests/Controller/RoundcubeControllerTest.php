<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RoundcubeControllerTest extends WebTestCase
{
    public function testGetUserAliasesWrongCredentials(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/roundcube');

        self::assertResponseStatusCodeSame(401);
    }

    public function testGetUserAliases(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get('doctrine')->getRepository(User::class);
        $client->loginUser($userRepository->findOneByEmail('user2@example.org'));
        $client->request('GET', '/api/roundcube');

        self::assertResponseIsSuccessful();

        $expected = [
            'alias@example.org',
            'alias2@example.org',
        ];
        $data = json_decode($client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($expected, $data);
    }
}
