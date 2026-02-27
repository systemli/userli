<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\DomainRepository;

final readonly class MtaStsService
{
    public function __construct(
        private DomainRepository $domainRepository,
        private SettingsService $settingsService,
    ) {
    }

    /**
     * Generate the MTA-STS policy for the given host.
     *
     * Returns the policy as a string, or null if no policy should be served.
     */
    public function getPolicy(string $host): ?string
    {
        $domain = $this->extractDomain($host);

        if (null === $domain || !$this->domainRepository->existsByName($domain)) {
            return null;
        }

        $mode = $this->settingsService->get('mta_sts_mode', 'testing');
        $mxValue = (string) $this->settingsService->get('mta_sts_mx', '');
        $maxAge = $this->settingsService->get('mta_sts_max_age', 604800);

        $mxHosts = $this->parseMxHosts($mxValue);

        if ('none' !== $mode && [] === $mxHosts) {
            return null;
        }

        $lines = [
            'version: STSv1',
            sprintf('mode: %s', $mode),
        ];

        foreach ($mxHosts as $mx) {
            $lines[] = sprintf('mx: %s', $mx);
        }

        $lines[] = sprintf('max_age: %d', $maxAge);

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * Extract the domain from the Host header by stripping the "mta-sts." prefix.
     *
     * Example: "mta-sts.example.org" → "example.org"
     */
    private function extractDomain(string $host): ?string
    {
        $host = strtolower($host);

        if (!str_starts_with($host, 'mta-sts.')) {
            return null;
        }

        $domain = substr($host, strlen('mta-sts.'));

        if ('' === $domain) {
            return null;
        }

        return $domain;
    }

    /**
     * Parse MX hosts from a textarea value (one host per line).
     *
     * @return list<string>
     */
    private function parseMxHosts(string $value): array
    {
        if ('' === trim($value)) {
            return [];
        }

        $lines = preg_split('/\r?\n/', $value);

        if (false === $lines) {
            return [];
        }

        $hosts = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ('' !== $line) {
                $hosts[] = $line;
            }
        }

        return $hosts;
    }
}
