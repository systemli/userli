<?php

namespace App\Tests\Security\Encoder;

use App\Security\Encoder\PasswordHashEncoder;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class PasswordHashEncoderTest extends TestCase
{
    public const PASSWORD = 'password';

    public function testValidationWithConfig(): void
    {
        $encoder = new PasswordHashEncoder('sha256', false, 1000);
        $result = $encoder->encodePassword(self::PASSWORD, null);
        self::assertTrue($encoder->isPasswordValid($result, self::PASSWORD, null));
        self::assertFalse($encoder->isPasswordValid($result, 'anotherPassword', null));
    }

    public function testValidationWithWrongAlgorithm(): void
    {
        $this->expectException(LogicException::class);
        $encoder = new PasswordHashEncoder('sha666');
        $result = $encoder->encodePassword(self::PASSWORD, null);
    }

    public function testValidation(): void
    {
        $encoder = new PasswordHashEncoder();
        $result = $encoder->encodePassword(self::PASSWORD, null);
        self::assertTrue($encoder->isPasswordValid($result, self::PASSWORD, null));
        self::assertFalse($encoder->isPasswordValid($result, 'anotherPassword', null));
    }

    public function testEncodePasswordLength(): void
    {
        $this->expectException(BadCredentialsException::class);
        $encoder = new PasswordHashEncoder();
        $encoder->encodePassword(str_repeat('a', 4097), 'salt');
    }

    public function testCheckPasswordLength(): void
    {
        $encoder = new PasswordHashEncoder();
        $result = $encoder->encodePassword(str_repeat('a', 4096), null);
        self::assertFalse($encoder->isPasswordValid($result, str_repeat('a', 4097), null));
        self::assertTrue($encoder->isPasswordValid($result, str_repeat('a', 4096), null));
    }

    public function testUserProvidedSaltIsNotUsed(): void
    {
        $encoder = new PasswordHashEncoder();
        $result = $encoder->encodePassword(self::PASSWORD, 'salt');
        self::assertTrue($encoder->isPasswordValid($result, self::PASSWORD, 'anotherSalt'));
    }

    public function testMd5(): void
    {
        $encoder = new PasswordHashEncoder();
        // doveadm pw -s MD5-CRYPT -p password
        $result = '$1$Is0rXQe3$CdxfOUEEqjfKZWc03GpEg1';
        self::assertTrue($encoder->isPasswordValid($result, self::PASSWORD, 'salt'));
    }
}
