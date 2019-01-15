<?php

namespace App\Builder;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class RecoveryProcessMessageBuilder.
 *
 * @author doobry <doobry@systemli.org>
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
     *
     * @param TranslatorInterface $translator
     * @param string              $appUrl
     * @param string              $projectName
     */
    public function __construct(TranslatorInterface $translator, $appUrl, $projectName)
    {
        $this->translator = $translator;
        $this->appUrl = $appUrl;
        $this->projectName = $projectName;
    }

    /**
     * @param string $locale
     * @param string $email
     * @param string $time
     *
     * @return string
     */
    public function buildBody(string $locale, string $email, string $time)
    {
        $body = $this->translator->trans('recovery.email-body', ['%app_url%' => $this->appUrl, '%project_name%' => $this->projectName, '%email%' => $email, '%time%' => $time], null, $locale);

        return $body;
    }

    /**
     * @param string $locale
     * @param string $email
     *
     * @return string
     */
    public function buildSubject(string $locale, string $email)
    {
        $subject = $this->translator->trans('recovery.email-subject', ['%email%' => $email], null, $locale);

        return $subject;
    }
}
