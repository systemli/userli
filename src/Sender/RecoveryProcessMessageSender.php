<?php

namespace App\Sender;

use App\Builder\RecoveryProcessMessageBuilder;
use App\Entity\User;
use App\Handler\MailHandler;

/**
 * Class WelcomeMessageSender.
 *
 * @author doobry <doobry@systemli.org>
 */
class RecoveryProcessMessageSender
{
    /**
     * @var MailHandler
     */
    private $handler;
    /**
     * @var RecoveryProcessMessageBuilder
     */
    private $builder;

    /**
     * WelcomeMessageSender constructor.
     *
     * @param MailHandler                   $handler
     * @param RecoveryProcessMessageBuilder $builder
     */
    public function __construct(MailHandler $handler, RecoveryProcessMessageBuilder $builder)
    {
        $this->handler = $handler;
        $this->builder = $builder;
    }

    /**
     * @param User   $user
     * @param string $locale
     *
     * @throws \Exception
     */
    public function send(User $user, string $locale)
    {
        $email = $user->getEmail();
        $formatter = \IntlDateFormatter::create($locale, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT);
        $time = $formatter->format($user->getRecoveryStartTime()->add(new \DateInterval('P2D')));

        $body = $this->builder->buildBody($locale, $email, $time);
        $subject = $this->builder->buildSubject($locale, $email);
        $this->handler->send($email, $body, $subject);
    }
}
