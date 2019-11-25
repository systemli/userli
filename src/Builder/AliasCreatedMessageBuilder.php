<?php

namespace App\Builder;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AliasCreatedMessageBuilder.
 */
class AliasCreatedMessageBuilder
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
     * AliasCreatedMessageBuilder constructor.
     *
     * @param string $appUrl
     * @param string $projectName
     */
    public function __construct(TranslatorInterface $translator, $appUrl, $projectName)
    {
        $this->translator = $translator;
        $this->appUrl = $appUrl;
        $this->projectName = $projectName;
    }

    /**
     * @return string
     */
    public function buildBody(string $locale, string $email, string $alias)
    {
        $body = $this->translator->trans(
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

        return $body;
    }

    /**
     * @return string
     */
    public function buildSubject(string $locale, string $email)
    {
        $subject = $this->translator->trans(
            'mail.alias-created-subject', ['%email%' => $email], null, $locale
        );

        return $subject;
    }
}
