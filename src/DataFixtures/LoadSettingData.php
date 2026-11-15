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
 * redirects point to the dev URL instead of the example domain.
 *
 * No WebAuthn RP ID is set, so it is the host of app_url. A shared RP ID like "localhost"
 * would be rejected by browsers on userli.localhost, as "localhost" is a public suffix.
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
    }

    #[Override]
    public static function getGroups(): array
    {
        return ['dev'];
    }
}
