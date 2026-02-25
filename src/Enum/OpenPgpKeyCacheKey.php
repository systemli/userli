<?php

declare(strict_types=1);

namespace App\Enum;

use App\Handler\WkdHandler;

enum OpenPgpKeyCacheKey: string
{
    public const int TTL = 86400; // 24 hours

    case WKD_LOOKUP = 'wkd_lookup_';

    public function ttl(): int
    {
        return self::TTL;
    }

    public function key(string $identifier): string
    {
        return $this->value.sha1($identifier);
    }

    /**
     * @return string[]
     */
    public static function allKeysForEmail(string $email): array
    {
        [$localPart, $domain] = explode('@', $email);

        $wkdHash = WkdHandler::wkdHash($localPart);

        return array_map(
            static fn (self $case) => $case->key(strtolower($wkdHash).'@'.strtolower($domain)),
            self::cases(),
        );
    }
}
