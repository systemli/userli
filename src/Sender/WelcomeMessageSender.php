<?php

namespace App\Sender;

use App\Builder\WelcomeMessageBuilder;
use App\Entity\User;
use App\Handler\MailHandler;

/**
 * Class WelcomeMessageSender.
 *
 * @author doobry <doobry@systemli.org>
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
     *
     * @param MailHandler           $handler
     * @param WelcomeMessageBuilder $builder
     * @param string                $domain
     */
    public function __construct(MailHandler $handler, WelcomeMessageBuilder $builder, string $domain)
    {
        $this->handler = $handler;
        $this->builder = $builder;
        $this->domain = $domain;
    }

    /**
     * @param User   $user
     * @param string $locale
     *
     * @throws \Exception
     */
    public function send(User $user, string $locale)
    {
        if (null === $email = $user->getEmail()) {
            throw new \Exception('Email should not be null');
        }

        if (strpos($email, $this->domain) < 0) {
            return;
        }

        $body = $this->builder->buildBody($locale);
        $subject = $this->builder->buildSubject();
        $this->handler->send($email, $body, $subject);
    }
}
