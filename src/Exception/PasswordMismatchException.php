<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

final class PasswordMismatchException extends Exception
{
    public function __construct(string $message = "The passwords don't match.")
    {
        parent::__construct($message);
    }
}
