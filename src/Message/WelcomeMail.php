<?php

declare(strict_types=1);

namespace App\Message;

final class WelcomeMail
{
    public function __construct(public string $email, public string $locale)
    {
    }
}
