<?php

namespace App\Sender;

use App\Builder\WelcomeMessageBuilder;
use App\Entity\User;
use App\Handler\MailHandler;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class WelcomeMessageSender.
 */
class WelcomeMessageSender
{
    /**
     * @var MailHandler
     */
    private $handler;
    /**
     * @var WelcomeMessageBuilder
     */
    private $builder;
    /**
     * @var string
     */
    private $domain;

    /**
     * WelcomeMessageSender constructor.
     */
    public function __construct(MailHandler $handler, WelcomeMessageBuilder $builder, EntityManagerInterface $manager)
    {
        $this->handler = $handler;
        $this->builder = $builder;
        $domain = $manager->getRepository('App:Domain')->getDefaultDomain();
        $this->domain = null !== $domain ? $domain->getName() : '';
    }

    /**
     * @throws \Exception
     */
    public function send(User $user, string $locale): void
    {
        if (null === $email = $user->getEmail()) {
            throw new \Exception('Email should not be null');
        }

        if (strpos($email, $this->domain) < 0) {
            return;
        }

        $body = $this->builder->buildBody($locale);
        $subject = $this->builder->buildSubject($locale);
        $this->handler->send($email, $body, $subject);
    }
}
