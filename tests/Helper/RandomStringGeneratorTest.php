<?php

namespace App\Tests\Helper;

use App\Helper\RandomStringGenerator;
use PHPUnit\Framework\TestCase;

class RandomStringGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $code = RandomStringGenerator::generate(20, false);

        self::assertNotEmpty($code);
        self::assertEquals(20, strlen($code));
        self::assertRegExp('/^[0-9a-z]+$/', $code);
        self::assertNotRegExp('/^[A-Z]+$/', $code);
    }

    public function testGenerateCaseSensitive(): void
    {
        $code = RandomStringGenerator::generate();

        self::assertNotEmpty($code);
        self::assertEquals(RandomStringGenerator::LENGTH, strlen($code));
        self::assertRegExp('/^[0-9a-zA-Z]+$/', $code);
    }
}
