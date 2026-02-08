<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\PasswordGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PasswordGeneratorTest extends TestCase
{
    #[DataProvider('provider')]
    public function testGenerate(int $length, int $iterations): void
    {
        $password = PasswordGenerator::generate($length);

        self::assertEquals($length, strlen($password));

        for ($i = 0; $i <= $iterations; ++$i) {
            self::assertNotEquals($password, PasswordGenerator::generate($length));
        }
    }

    public static function provider(): array
    {
        return [
            [45, 1000],
            [10, 100],
        ];
    }
}
