<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Enum\ApiScope;
use App\Service\ApiTokenManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class LoadApiTokenData extends Fixture implements FixtureGroupInterface
{
    public const RETENTION_TOKEN_PLAIN = 'b26b306e7c65785d9c5ad8821b59ce33';

    public const KEYCLOAK_TOKEN_PLAIN = '0f4a7af12bbb3d1f22072a8fff75d30a';

    public const DOVECOT_TOKEN_PLAIN = '727eb7d3ad310bc510f5fa17c223572c';

    public const POSTFIX_TOKEN_PLAIN = 'e38a0bc6b5de5b7c6137eb9d383d1977';

    public const ROUNDCUBE_TOKEN_PLAIN = '3ea02d4e9490e2a4d065ede69d6741a6';

    public function __construct(
        private readonly ApiTokenManager $apiTokenManager,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Create retention token
        $this->apiTokenManager->create(
            self::RETENTION_TOKEN_PLAIN,
            'Test Retention Token',
            [ApiScope::RETENTION->value]
        );

        // Create keycloak token
        $this->apiTokenManager->create(
            self::KEYCLOAK_TOKEN_PLAIN,
            'Test Keycloak Token',
            [ApiScope::KEYCLOAK->value]
        );

        // Create dovecot token
        $this->apiTokenManager->create(
            self::DOVECOT_TOKEN_PLAIN,
            'Test Dovecot Token',
            [ApiScope::DOVECOT->value]
        );

        // Create postfix token
        $this->apiTokenManager->create(
            self::POSTFIX_TOKEN_PLAIN,
            'Test Postfix Token',
            [ApiScope::POSTFIX->value]
        );

        // Create roundcube token
        $this->apiTokenManager->create(
            self::ROUNDCUBE_TOKEN_PLAIN,
            'Test Roundcube Token',
            [ApiScope::ROUNDCUBE->value]
        );
    }

    public static function getGroups(): array
    {
        return ['basic'];
    }
}
