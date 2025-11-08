<?php

declare(strict_types=1);

namespace App\Service;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;

readonly class SettingsAwareTotpAuthenticator implements TotpAuthenticatorInterface
{
    public function __construct(
        private TotpAuthenticatorInterface $decoratedAuthenticator,
        private SettingsService $settingsService,
    ) {
    }

    public function getQRContent($user): string
    {
        // Get the original QR content
        $qrContent = $this->decoratedAuthenticator->getQRContent($user);

        // Validate QR content
        if (empty($qrContent)) {
            return $qrContent;
        }

        // Parse the QR content URL to modify it
        $parsedUrl = parse_url($qrContent);
        if ($parsedUrl === false) {
            return $qrContent; // Return original if parsing fails
        }

        parse_str($parsedUrl['query'] ?? '', $params);

        // Update with dynamic settings
        $projectName = $this->settingsService->get('project_name');

        // Update issuer
        $params['issuer'] = $projectName;

        // Rebuild the QR content URL
        $parsedUrl['query'] = http_build_query($params);

        return $this->buildUrl($parsedUrl);
    }

    public function generateSecret(): string
    {
        return $this->decoratedAuthenticator->generateSecret();
    }

    public function checkCode($user, string $code): bool
    {
        return $this->decoratedAuthenticator->checkCode($user, $code);
    }

    private function buildUrl(array $parsedUrl): string
    {
        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'].'://' : '';
        $host = $parsedUrl['host'] ?? '';
        $port = isset($parsedUrl['port']) ? ':'.$parsedUrl['port'] : '';
        $path = $parsedUrl['path'] ?? '';
        $query = isset($parsedUrl['query']) ? '?'.$parsedUrl['query'] : '';
        $fragment = isset($parsedUrl['fragment']) ? '#'.$parsedUrl['fragment'] : '';

        return $scheme.$host.$port.$path.$query.$fragment;
    }
}
