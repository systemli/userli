<?php

namespace App\Tests\Security\Encoder;

use App\Security\Encoder\PasswordHashEncoder;
use PHPUnit\Framework\TestCase;

class PasswordHashEncoderTest extends TestCase
{
    const PASSWORD = 'password';

    public function testValidationWithConfig()
    {
        $encoder = new PasswordHashEncoder('sha256', false, 1000);
        $result = $encoder->encodePassword(self::PASSWORD, null);
        $this->assertTrue($encoder->isPasswordValid($result, self::PASSWORD, null));
        $this->assertFalse($encoder->isPasswordValid($result, 'anotherPassword', null));
    }

    /**
     * @expectedException \LogicException
     */
    public function testValidationWithWrongAlgorithm()
    {
        $encoder = new PasswordHashEncoder('sha666');
        $result = $encoder->encodePassword(self::PASSWORD, null);
    }

    public function testValidation()
    {
        $encoder = new PasswordHashEncoder();
        $result = $encoder->encodePassword(self::PASSWORD, null);
        $this->assertTrue($encoder->isPasswordValid($result, self::PASSWORD, null));
        $this->assertFalse($encoder->isPasswordValid($result, 'anotherPassword', null));
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testEncodePasswordLength()
    {
        $encoder = new PasswordHashEncoder();
        $encoder->encodePassword(str_repeat('a', 4097), 'salt');
    }

    public function testCheckPasswordLength()
    {
        $encoder = new PasswordHashEncoder();
        $result = $encoder->encodePassword(str_repeat('a', 4096), null);
        $this->assertFalse($encoder->isPasswordValid($result, str_repeat('a', 4097), null));
        $this->assertTrue($encoder->isPasswordValid($result, str_repeat('a', 4096), null));
    }

    public function testUserProvidedSaltIsNotUsed()
    {
        $encoder = new PasswordHashEncoder();
        $result = $encoder->encodePassword(self::PASSWORD, 'salt');
        $this->assertTrue($encoder->isPasswordValid($result, self::PASSWORD, 'anotherSalt'));
    }
}
