<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

/**
 * Thrown when an alias can't be converted into a user.
 *
 * The message is English and meant for logs and the console, the reason is a
 * translation key for the web interface.
 */
final class AliasConversionException extends Exception
{
    private function __construct(string $message, private readonly string $reason)
    {
        parent::__construct($message);
    }

    public static function aliasDeleted(string $source): self
    {
        return new self(sprintf('Alias %s is deleted.', $source), 'admin.alias.convert.error.deleted');
    }

    public static function aliasRandom(string $source): self
    {
        return new self(sprintf('Alias %s is a random alias.', $source), 'admin.alias.convert.error.random');
    }

    public static function domainNotFound(string $source): self
    {
        return new self(sprintf('The domain of alias %s is not managed by this instance.', $source), 'admin.alias.convert.error.domain');
    }

    public static function userExists(string $source): self
    {
        return new self(sprintf('A user with the address %s exists or existed before.', $source), 'admin.alias.convert.error.user_exists');
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
