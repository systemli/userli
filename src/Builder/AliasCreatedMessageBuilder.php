<?php

namespace App\Builder;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class AliasCreatedMessageBuilder.
 */
class AliasCreatedMessageBuilder
{
    /**
     * AliasCreatedMessageBuilder constructor.
     */
    public function __construct(private readonly TranslatorInterface $translator, private readonly string $appUrl, private readonly string $projectName)
    {
    }

    public function buildBody(string $locale, string $email, string $alias): string
    {
        return $this->translator->trans(
            'mail.alias-created-body',
            [
                '%app_url%' => $this->appUrl,
                '%project_name%' => $this->projectName,
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
            'mail.alias-created-subject', ['%email%' => $email], null, $locale
        );
    }
}
