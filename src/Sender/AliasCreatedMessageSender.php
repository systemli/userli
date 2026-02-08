<?php

declare(strict_types=1);

namespace App\Sender;

use App\Builder\AliasCreatedMessageBuilder;
use App\Entity\Alias;
use App\Entity\User;
use App\Handler\MailHandler;
use Exception;

/**
 * Class AliasCreatedMessageSender.
 */
final readonly class AliasCreatedMessageSender
{
    /**
     * AliasCreatedMessageSender constructor.
     */
    public function __construct(private MailHandler $handler, private AliasCreatedMessageBuilder $builder)
    {
    }

    /**
     * @throws Exception
     */
    public function send(User $user, Alias $alias, string $locale): void
    {
        if (null === $email = $user->getEmail()) {
            throw new Exception('Email should not be null');
        }

        $body = $this->builder->buildBody($locale, $email, $alias->getSource());
        $subject = $this->builder->buildSubject($locale, $email);
        $this->handler->send($email, $body, $subject);
    }
}
