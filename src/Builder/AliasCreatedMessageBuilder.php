<?php

declare(strict_types=1);

namespace App\Builder;

use App\Service\SettingsService;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AliasCreatedMessageBuilder
{
    public function __construct(
        private TranslatorInterface $translator,
        private SettingsService $settingsService,
    ) {
    }

    public function buildBody(string $locale, string $email, string $alias): string
    {
        return $this->translator->trans(
            'mail.alias-created-body',
            [
                '%app_url%' => $this->settingsService->get('app_url'),
                '%project_name%' => $this->settingsService->get('project_name'),
                '%email%' => $email,
                '%alias%' => $alias,
            ],
            null,
            $locale
        );
    }

    public function buildSubject(string $locale, string $email): string
    {
        return $this->translator->trans(
            'mail.alias-created-subject',
            ['%email%' => $email],
            null,
            $locale
        );
    }
}
