<?php

declare(strict_types=1);

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final class WelcomeMail
{
    public function __construct(public string $email, public string $locale)
    {
    }
}
