<?php

declare(strict_types=1);

namespace App\Builder;

use App\Service\SettingsService;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class RecoveryProcessMessageBuilder
{
    public function __construct(
        private TranslatorInterface $translator,
        private SettingsService $settingsService,
    ) {
    }

    public function buildBody(string $locale, string $email, string $time): string
    {
        return $this->translator->trans(
            'mail.recovery-body',
            [
                '%app_url%' => $this->settingsService->get('app_url'),
                '%project_name%' => $this->settingsService->get('project_name'),
                '%email%' => $email,
                '%time%' => $time,
            ],
            null,
            $locale
        );
    }

    public function buildSubject(string $locale, string $email): string
    {
        return $this->translator->trans(
            'mail.recovery-subject',
            ['%email%' => $email],
            null,
            $locale
        );
    }
}
