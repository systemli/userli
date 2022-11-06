<?php

namespace App\Tests\Security\Encoder;

use App\Security\Encoder\LegacyPasswordHasher;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Exception\InvalidPasswordException;

class PasswordHashEncoderTest extends TestCase
{
    public const PASSWORD = 'password';

    public function testValidationWithConfig(): void
    {
        $hasher = new LegacyPasswordHasher('sha256', false, 1000);
        $result = $hasher->hash(self::PASSWORD);
        self::assertTrue($hasher->verify($result, self::PASSWORD));
        self::assertFalse($hasher->verify($result, 'anotherPassword'));
    }

    public function testValidationWithWrongAlgorithm(): void
    {
        $this->expectException(LogicException::class);
        $hasher = new LegacyPasswordHasher('sha666');
        $hasher->hash(self::PASSWORD);
    }

    public function testValidation(): void
    {
        $hasher = new LegacyPasswordHasher();
        $result = $hasher->hash(self::PASSWORD);
        self::assertTrue($hasher->verify($result, self::PASSWORD));
        self::assertFalse($hasher->verify($result, 'anotherPassword'));
    }

    public function testEncodePasswordLength(): void
    {
        $this->expectException(InvalidPasswordException::class);
        $hasher = new LegacyPasswordHasher();
        $hasher->hash(str_repeat('a', 4097));
    }

    public function testCheckPasswordLength(): void
    {
        $hasher = new LegacyPasswordHasher();
        $result = $hasher->hash(str_repeat('a', 4096));
        self::assertFalse($hasher->verify($result, str_repeat('a', 4097)));
        self::assertTrue($hasher->verify($result, str_repeat('a', 4096)));
    }

    public function testMd5(): void
    {
        $hasher = new LegacyPasswordHasher();
        // doveadm pw -s MD5-CRYPT -p password
        $result = '$1$Is0rXQe3$CdxfOUEEqjfKZWc03GpEg1';
        self::assertTrue($hasher->verify($result, self::PASSWORD));
    }
}
