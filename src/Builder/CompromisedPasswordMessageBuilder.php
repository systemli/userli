<?php

declare(strict_types=1);

namespace App\Builder;

use Symfony\Contracts\Translation\TranslatorInterface;

readonly class CompromisedPasswordMessageBuilder
{
    public function __construct(
        private TranslatorInterface $translator,
        private string              $appUrl,
        private string              $projectName
    )
    {
    }

    public function buildBody(string $locale, string $email): string
    {
        return $this->translator->trans(
            'mail.compromised-password-body',
            [
                '%email%' => $email,
                '%app_url%' => $this->appUrl,
                '%project_name%' => $this->projectName,
            ],
            null,
            $locale
        );
    }

    public function buildSubject(string $locale): string
    {
        return $this->translator->trans(
            'mail.compromised-password-subject',
            [
                '%project_name%' => $this->projectName,
            ],
            null,
            $locale
        );
    }
}
