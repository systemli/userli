<?php

namespace App\Tests\Helper;

use App\Helper\VoucherCodeGenerator;
use PHPUnit\Framework\TestCase;

class VoucherCodeGeneratorTest extends TestCase
{
    public function testGenerate()
    {
        $code = VoucherCodeGenerator::generate();

        self::assertNotEmpty($code);
        self::assertEquals(VoucherCodeGenerator::LENGTH, strlen($code));
        self::assertRegExp('/[0-9a-zA-Z]*/', $code);
    }
}
