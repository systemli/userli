<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

final class PasswordPolicyException extends Exception
{
    public function __construct(string $message = "The password doesn't comply with our security policy.")
    {
        parent::__construct($message);
    }
}
