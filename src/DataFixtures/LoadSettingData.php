<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Service\SettingsService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

/**
 * Settings for the local dev environment (group "dev"), so links in emails and
 * redirects point to the dev URL instead of the example domain, and security keys
 * work on Userli, Keycloak and Roundcube.
 */
final class LoadSettingData extends Fixture implements FixtureGroupInterface
{
    public function __construct(private readonly SettingsService $settingsService)
    {
    }

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $appUrl = getenv('DEV_APP_URL');
        $appUrl = false !== $appUrl && '' !== $appUrl ? $appUrl : 'http://userli.localhost';

        // Compose always appends the port, the default port is not part of the URL
        $appUrl = (string) preg_replace('/:80$/', '', $appUrl);
        $this->settingsService->set('app_url', $appUrl);

        // The RP ID has to be a suffix of every origin, so it is the shared parent of
        // userli.localhost, keycloak.localhost and roundcube.localhost. The Keycloak
        // realm uses the same RP ID (docker/keycloak/realm-userli.json).
        $this->settingsService->set('webauthn_rp_id', 'localhost');

        $port = parse_url($appUrl, PHP_URL_PORT);
        $suffix = is_int($port) ? ':'.$port : '';
        $this->settingsService->set('webauthn_origins', implode("\n", [
            'http://keycloak.localhost'.$suffix,
            'http://roundcube.localhost'.$suffix,
        ]));
    }

    #[Override]
    public static function getGroups(): array
    {
        return ['dev'];
    }
}
