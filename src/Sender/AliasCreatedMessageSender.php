<?php

namespace App\Sender;

use App\Builder\AliasCreatedMessageBuilder;
use App\Entity\Alias;
use App\Entity\User;
use App\Handler\MailHandler;

/**
 * Class AliasCreatedMessageSender.
 */
class AliasCreatedMessageSender
{
    /**
     * @var MailHandler
     */
    private $handler;
    /**
     * @var AliasCreatedMessageBuilder
     */
    private $builder;

    /**
     * AliasCreatedMessageSender constructor.
     */
    public function __construct(MailHandler $handler, AliasCreatedMessageBuilder $builder)
    {
        $this->handler = $handler;
        $this->builder = $builder;
    }

    /**
     * @throws \Exception
     */
    public function send(User $user, Alias $alias, string $locale): void
    {
        if (null === $email = $user->getEmail()) {
            throw new \Exception('Email should not be null');
        }

        $body = $this->builder->buildBody($locale, $email, $alias->getSource());
        $subject = $this->builder->buildSubject($locale, $email);
        $this->handler->send($email, $body, $subject);
    }
}
