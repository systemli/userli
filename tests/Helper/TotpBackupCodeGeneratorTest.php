<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\TotpBackupCodeGenerator;
use PHPUnit\Framework\TestCase;

class TotpBackupCodeGeneratorTest extends TestCase
{
    public function testGenerateReturnsCorrectCount(): void
    {
        $generator = new TotpBackupCodeGenerator();
        $codes = $generator->generate(6);

        self::assertCount(6, $codes);
    }

    public function testGenerateReturnsCustomCount(): void
    {
        $generator = new TotpBackupCodeGenerator();
        $codes = $generator->generate(3);

        self::assertCount(3, $codes);
    }

    public function testGenerateReturnsSixDigitCodes(): void
    {
        $generator = new TotpBackupCodeGenerator();
        $codes = $generator->generate(10);

        foreach ($codes as $code) {
            self::assertMatchesRegularExpression('/^\d{6}$/', $code);
        }
    }

    public function testGenerateReturnsStrings(): void
    {
        $generator = new TotpBackupCodeGenerator();
        $codes = $generator->generate();

        foreach ($codes as $code) {
            self::assertIsString($code);
        }
    }

    public function testGenerateDefaultsToSixCodes(): void
    {
        $generator = new TotpBackupCodeGenerator();
        $codes = $generator->generate();

        self::assertCount(6, $codes);
    }

    public function testGenerateReturnsUniqueishCodes(): void
    {
        $generator = new TotpBackupCodeGenerator();
        // Generate enough codes that duplicates are extremely unlikely
        $codes = $generator->generate(6);

        // While not guaranteed, 6 codes from a range of 100000-999999 should be unique
        self::assertSame(count($codes), count(array_unique($codes)));
    }
}
