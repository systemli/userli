<?php

namespace App\Builder;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class WelcomeMessageBuilder.
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
     */
    public function __construct(TranslatorInterface $translator, ObjectManager $manager, string $appUrl, string $projectName)
    {
        $this->translator = $translator;
        $this->domain = $manager->getRepository('App:Domain')->getDefaultDomain()->getName();
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
        $body = $this->translator->trans(
            'mail.welcome-body',
            ['%app_url%' => $this->appUrl, '%project_name%' => $this->projectName],
            null,
            $locale
        );

        return $body;
    }

    /**
     * @param $locale
     *
     * @return string
     */
    public function buildSubject($locale)
    {
        $subject = $this->translator->trans(
            'mail.welcome-subject',
            ['%domain%' => $this->domain],
            null,
            $locale
        );

        return $subject;
    }
}
