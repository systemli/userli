<?php

declare(strict_types=1);

namespace App\Sender;

use App\Builder\RecoveryProcessMessageBuilder;
use App\Entity\User;
use App\Handler\MailHandler;
use DateInterval;
use Exception;
use IntlDateFormatter;

/**
 * Class RecoveryProcessMessageSender.
 */
class RecoveryProcessMessageSender
{
    /**
     * RecoveryProcessMessageSender constructor.
     */
    public function __construct(private readonly MailHandler $handler, private readonly RecoveryProcessMessageBuilder $builder)
    {
    }

    /**
     * @throws Exception
     */
    public function send(User $user, string $locale): void
    {
        if (null === $email = $user->getEmail()) {
            throw new Exception('Email should not be null');
        }

        $formatter = IntlDateFormatter::create($locale, IntlDateFormatter::MEDIUM, IntlDateFormatter::SHORT);
        $time = $formatter->format($user->getRecoveryStartTime()->add(new DateInterval('P2D')));

        $body = $this->builder->buildBody($locale, $email, $time);
        $subject = $this->builder->buildSubject($locale, $email);
        $this->handler->send($email, $body, $subject);
    }
}
