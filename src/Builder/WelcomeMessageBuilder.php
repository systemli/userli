<?php

declare(strict_types=1);

namespace App\Builder;

use App\Service\SettingsService;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class WelcomeMessageBuilder
{
    public function __construct(
        private TranslatorInterface $translator,
        private SettingsService $settingsService,
    ) {
    }

    public function buildBody(string $locale): string
    {
        return $this->translator->trans(
            'mail.welcome-body',
            [
                '%app_url%' => $this->settingsService->get('app_url'),
                '%project_name%' => $this->settingsService->get('project_name'),
            ],
            null,
            $locale
        );
    }

    public function buildSubject(string $locale): string
    {
        return $this->translator->trans(
            'mail.welcome-subject',
            ['%project_name%' => $this->settingsService->get('project_name')],
            null,
            $locale
        );
    }
}
