<?php

declare(strict_types=1);

namespace App\Enum;

enum AliasCacheKey: string
{
    public const int TTL = 86400; // 24 hours

    case POSTFIX_ALIAS = 'postfix_alias_';

    public function ttl(): int
    {
        return self::TTL;
    }

    public function key(string $source): string
    {
        return $this->value.sha1($source);
    }

    /**
     * @return string[]
     */
    public static function allKeysForSource(string $source): array
    {
        return array_map(
            static fn (self $case) => $case->key($source),
            self::cases(),
        );
    }
}
