<?php

namespace App\Builder;

use App\Entity\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class WelcomeMessageBuilder.
 */
class WelcomeMessageBuilder
{
    private $domain;

    /**
     * WelcomeMessageBuilder constructor.
     */
    public function __construct(private readonly TranslatorInterface $translator, EntityManagerInterface $manager, private readonly string $appUrl, private readonly string $projectName)
    {
        $domain = $manager->getRepository(Domain::class)->getDefaultDomain();
        $this->domain = null !== $domain ? $domain->getName() : '';
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
