<?php

namespace App\Builder;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class RecoveryProcessMessageBuilder.
 */
class RecoveryProcessMessageBuilder
{
    /**
     * RecoveryProcessMessageBuilder constructor.
     */
    public function __construct(private TranslatorInterface $translator, private string $appUrl, private string $projectName)
    {
    }

    public function buildBody(string $locale, string $email, string $time): string
    {
        return $this->translator->trans(
            'mail.recovery-body',
            [
                '%app_url%' => $this->appUrl,
                '%project_name%' => $this->projectName,
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
            'mail.recovery-subject', ['%email%' => $email], null, $locale
        );
    }
}
