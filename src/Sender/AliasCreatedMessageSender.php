<?php

namespace App\Sender;

use App\Builder\AliasCreatedMessageBuilder;
use App\Entity\Alias;
use App\Entity\User;
use App\Handler\MailHandler;

/**
 * Class AliasCreatedMessageSender.
 *
 * @author doobry <doobry@systemli.org>
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
     *
     * @param MailHandler                $handler
     * @param AliasCreatedMessageBuilder $builder
     */
    public function __construct(MailHandler $handler, AliasCreatedMessageBuilder $builder)
    {
        $this->handler = $handler;
        $this->builder = $builder;
    }

    /**
     * @param User   $user
     * @param Alias  $alias
     * @param string $locale
     *
     * @throws \Exception
     */
    public function send(User $user, Alias $alias, string $locale)
    {
        if (null === $email = $user->getEmail()) {
            throw new \Exception('Email should not be null');
        }

        if (null === $aliasSource = $alias->getSource()) {
            throw new \Exception('Alias should not be null');
        }

        $body = $this->builder->buildBody($locale, $email, $aliasSource);
        $subject = $this->builder->buildSubject($locale, $email);
        $this->handler->send($email, $body, $subject);
    }
}
