<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Service\SettingsService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Override;

use const PHP_URL_HOST;
use const PHP_URL_PORT;
use const PHP_URL_SCHEME;

/**
 * Settings for the local dev environment (group "dev"), so links in emails and
 * redirects point to the dev URL instead of the example domain, and security keys
 * work on Userli, Roundcube (webmail_url) and Keycloak.
 *
 * All services are subdomains of userli.localhost, which is the WebAuthn RP ID. Browsers
 * reject "localhost" itself as RP ID on subdomains, as it is treated like a top-level domain.
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
        $appUrl = false !== $appUrl && '' !== $appUrl ? $appUrl : 'http://app.userli.localhost';

        // Compose always appends the port, the default port is not part of the URL
        $appUrl = (string) preg_replace('/:80$/', '', $appUrl);

        $this->settingsService->set('app_url', $appUrl);

        $scheme = parse_url($appUrl, PHP_URL_SCHEME);
        $host = (string) parse_url($appUrl, PHP_URL_HOST);
        $port = parse_url($appUrl, PHP_URL_PORT);
        $suffix = is_int($port) ? ':'.$port : '';
        $rpId = (string) preg_replace('/^[^.]+\./', '', $host);

        // The Keycloak realm uses the same RP ID (docker/keycloak/realm-userli.json)
        $this->settingsService->set('webauthn_rp_id', $rpId);
        $this->settingsService->set('webmail_url', $scheme.'://roundcube.'.$rpId.$suffix);
    }

    #[Override]
    public static function getGroups(): array
    {
        return ['dev'];
    }
}
