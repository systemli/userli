<?php

namespace App\Builder;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class WelcomeMessageBuilder.
 *
 * @author doobry <doobry@systemli.org>
 */
class WelcomeMessageBuilder
{
    /**
     * @var TranslatorInterface
     */
    private $translator;
    /**
     * @var string
     */
    private $domain;
    /**
     * @var string
     */
    private $appUrl;
    /**
     * @var string
     */
    private $projectName;

    /**
     * WelcomeMessageBuilder constructor.
     *
     * @param TranslatorInterface $translator
     * @param string              $domain
     * @param string              $appUrl
     * @param string              $projectName
     */
    public function __construct(TranslatorInterface $translator, $domain, $appUrl, $projectName)
    {
        $this->translator = $translator;
        $this->domain = $domain;
        $this->appUrl = $appUrl;
        $this->projectName = $projectName;
    }

    /**
     * @param $locale
     *
     * @return string
     */
    public function buildBody($locale)
    {
        $body = $this->translator->trans('mail.welcome-body', ['%app_url%' => $this->appUrl, '%project_name%' => $this->projectName], null, $locale);

        return $body;
    }

    /**
     * @param $locale
     *
     * @return string
     */
    public function buildSubject($locale)
    {
        $subject = $this->translator->trans('mail.welcome-subject', ['%domain%' => $this->domain], null, $locale);

        return $subject;
    }
}
