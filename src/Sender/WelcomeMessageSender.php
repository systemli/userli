<?php

namespace App\Sender;

use Exception;
use App\Builder\WelcomeMessageBuilder;
use App\Entity\Domain;
use App\Entity\User;
use App\Handler\MailHandler;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class WelcomeMessageSender.
 */
class WelcomeMessageSender
{
    private string $domain;

    /**
     * WelcomeMessageSender constructor.
     */
    public function __construct(private readonly MailHandler $handler, private readonly WelcomeMessageBuilder $builder, EntityManagerInterface $manager)
    {
        $domain = $manager->getRepository(Domain::class)->getDefaultDomain();
        $this->domain = null !== $domain ? $domain->getName() : '';
    }

    /**
     * @throws Exception
     */
    public function send(User $user, string $locale): void
    {
        if (null === $email = $user->getEmail()) {
            throw new Exception('Email should not be null');
        }

        if (strpos($email, (string) $this->domain) < 0) {
            return;
        }

        $body = $this->builder->buildBody($locale);
        $subject = $this->builder->buildSubject($locale);
        $this->handler->send($email, $body, $subject);
    }
}
