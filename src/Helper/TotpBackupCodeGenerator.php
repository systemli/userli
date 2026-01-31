<?php

declare(strict_types=1);

namespace App\Helper;

use Random\RandomException;

final readonly class TotpBackupCodeGenerator
{
    /**
     * @return array<string>
     *
     * @throws RandomException
     */
    public function generate(int $count = 6): array
    {
        $codes = [];
        for ($i = 0; $i < $count; ++$i) {
            $codes[] = (string) random_int(100000, 999999);
        }

        return $codes;
    }
}
