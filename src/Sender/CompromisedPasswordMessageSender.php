<?php

declare(strict_types=1);

namespace App\Sender;

use App\Builder\CompromisedPasswordMessageBuilder;
use App\Entity\User;
use App\Handler\MailHandler;

readonly class CompromisedPasswordMessageSender
{
    public function __construct(
        private MailHandler                       $handler,
        private CompromisedPasswordMessageBuilder $builder
    )
    {
    }

    public function send(User $user, string $locale): void
    {
        $email = $user->getEmail();
        $body = $this->builder->buildBody($locale, $email);
        $subject = $this->builder->buildSubject($locale);

        $this->handler->send($email, $body, $subject);
    }
}
