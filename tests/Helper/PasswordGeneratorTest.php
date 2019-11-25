<?php

namespace App\Tests\Helper;

use App\Helper\PasswordGenerator;
use PHPUnit\Framework\TestCase;

class PasswordGeneratorTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testGenerate(int $length, int $iterations)
    {
        $password = PasswordGenerator::generate($length);

        self::assertEquals($length, strlen($password));

        for ($i = 0; $i <= $iterations; ++$i) {
            self::assertNotEquals($password, PasswordGenerator::generate($length));
        }
    }

    /**
     * @return array
     */
    public function provider()
    {
        return [
            [45, 1000],
            [10, 100],
        ];
    }
}
