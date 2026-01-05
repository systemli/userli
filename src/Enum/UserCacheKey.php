<?php

declare(strict_types=1);

namespace App\Enum;

enum UserCacheKey: string
{
    public const int TTL = 86400; // 24 hours

    case DOVECOT_LOOKUP = 'dovecot_lookup_';
    case POSTFIX_MAILBOX = 'postfix_mailbox_';
    case POSTFIX_QUOTA = 'postfix_quota_';
    case POSTFIX_SENDERS = 'postfix_senders_';

    public function ttl(): int
    {
        return self::TTL;
    }

    public function key(string $email): string
    {
        return $this->value.sha1($email);
    }

    /**
     * @return string[]
     */
    public static function allKeysForEmail(string $email): array
    {
        return array_map(
            static fn (self $case) => $case->key($email),
            self::cases(),
        );
    }
}
