<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\AliasRepository;

final readonly class RfcAliasResolver
{
    private const array RFC_ADDRESS_SETTINGS = [
        'postmaster' => 'postmaster_address',
        'abuse' => 'abuse_address',
    ];

    public function __construct(
        private AliasRepository $aliasRepository,
        private SettingsService $settingsService,
    ) {
    }

    /**
     * Check if the source address matches an RFC address prefix.
     */
    public function isRfcAddress(string $source): bool
    {
        $localPart = strstr($source, '@', true);

        return false !== $localPart && isset(self::RFC_ADDRESS_SETTINGS[$localPart]);
    }

    /**
     * Resolve destinations for an email alias source.
     *
     * Checks RFC address settings first, then falls back to database aliases.
     *
     * @return list<string>
     */
    public function resolveDestinations(string $source): array
    {
        $localPart = strstr($source, '@', true);

        if (false !== $localPart && isset(self::RFC_ADDRESS_SETTINGS[$localPart])) {
            $settingKey = self::RFC_ADDRESS_SETTINGS[$localPart];
            $destination = (string) $this->settingsService->get($settingKey, '');

            if ('' !== $destination) {
                return [$destination];
            }
        }

        return $this->aliasRepository->findDestinationsBySource($source);
    }
}
