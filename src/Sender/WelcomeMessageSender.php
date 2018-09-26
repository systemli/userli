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
    public function __construct(MailHandler $handler, WelcomeMessageBuilder $builder, $domain)
    {
        $this->handler = $handler;
        $this->builder = $builder;
        $this->domain = $domain;
    }

    /**
     * @param User $user
     * @param $locale
     */
    public function send(User $user, $locale)
    {
        if (strpos($email = $user->getEmail(), $this->domain) < 0) {
            return;
        }

        $body = $this->builder->buildBody($locale);
        $subject = $this->builder->buildSubject();
        $this->handler->send($user->getEmail(), $body, $subject);
    }
}
