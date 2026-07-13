<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\AliasRepository;
use App\Repository\DomainRepository;

final readonly class RfcAliasResolver
{
    private const array RFC_ADDRESS_SETTINGS = [
        'postmaster' => 'postmaster_address',
        'abuse' => 'abuse_address',
    ];

    public function __construct(
        private AliasRepository $aliasRepository,
        private DomainRepository $domainRepository,
        private SettingsService $settingsService,
    ) {
    }

    /**
     * Check if the source address matches an RFC address prefix on a managed domain.
     */
    public function isRfcAddress(string $source): bool
    {
        $localPart = strstr($source, '@', true);

        if (false === $localPart || !isset(self::RFC_ADDRESS_SETTINGS[$localPart])) {
            return false;
        }

        return $this->domainRepository->existsByName(substr($source, strlen($localPart) + 1));
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
        if ($this->isRfcAddress($source)) {
            $localPart = strstr($source, '@', true);
            $settingKey = self::RFC_ADDRESS_SETTINGS[$localPart];
            $destination = (string) $this->settingsService->get($settingKey, '');

            if ('' !== $destination) {
                return [$destination];
            }
        }

        return $this->aliasRepository->findDestinationsBySource($source);
    }
}
