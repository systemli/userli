<?php

declare(strict_types=1);

namespace App\Enum;

enum DomainCacheKey: string
{
    public const int TTL = 86400; // 24 hours

    case POSTFIX_DOMAIN = 'postfix_domain_';

    public function ttl(): int
    {
        return self::TTL;
    }

    public function key(string $name): string
    {
        return $this->value.sha1($name);
    }

    /**
     * @return string[]
     */
    public static function allKeysForName(string $name): array
    {
        return array_map(
            static fn (self $case) => $case->key($name),
            self::cases(),
        );
    }
}
