<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class NoGpgKeyForUserException extends RuntimeException
{
}
