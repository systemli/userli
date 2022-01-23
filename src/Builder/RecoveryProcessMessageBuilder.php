<?php

namespace App\Builder;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class RecoveryProcessMessageBuilder.
 */
class RecoveryProcessMessageBuilder
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var string
     */
    private $appUrl;
    /**
     * @var string
     */
    private $projectName;

    /**
     * RecoveryProcessMessageBuilder constructor.
     */
    public function __construct(TranslatorInterface $translator, string $appUrl, string $projectName)
    {
        $this->translator = $translator;
        $this->appUrl = $appUrl;
        $this->projectName = $projectName;
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
