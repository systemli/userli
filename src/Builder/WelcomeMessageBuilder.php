<?php

namespace App\Builder;

use Doctrine\ORM\EntityManagerInterface;
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
    public function __construct(TranslatorInterface $translator, EntityManagerInterface $manager, string $appUrl, string $projectName)
    {
        $this->translator = $translator;
        $domain = $manager->getRepository('App:Domain')->getDefaultDomain();
        $this->domain = null !== $domain ? $domain->getName() : '';
        $this->appUrl = $appUrl;
        $this->projectName = $projectName;
    }

    /**
     * @param $locale
     */
    public function buildBody($locale): string
    {
        return $this->translator->trans(
            'mail.welcome-body',
            ['%app_url%' => $this->appUrl, '%project_name%' => $this->projectName],
            null,
            $locale
        );
    }

    /**
     * @param $locale
     */
    public function buildSubject($locale): string
    {
        return $this->translator->trans(
            'mail.welcome-subject',
            ['%domain%' => $this->domain],
            null,
            $locale
        );
    }
}
