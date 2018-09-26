<?php

namespace App\Tests\Handler;

use App\Handler\PasswordStrengthHandler;
use PHPUnit\Framework\TestCase;

class PasswordStrengthHandlerTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     * @param $input
     * @param $expected
     */
    public function testValidate($input, $expected)
    {
        $handler = new PasswordStrengthHandler();
        $actual = $handler->validate($input);

        $this->assertEquals($expected, $actual);
    }

    public function dataProvider()
    {
        return array(
            array('password', array('form.weak_password')),
            array('Password', array('form.weak_password')),
            array('pässword', array('form.forbidden_char', 'form.weak_password')),
            array('PässwordSecure1', array('form.forbidden_char')),
            array('PasswördSecure1', array('form.forbidden_char')),
            array('PasswordSecüre1', array('form.forbidden_char')),
            array('PasswordSecure1\'', array('form.forbidden_char')),
            array('passwordpasswordpassword', array()),
            array('PasswordSecure1', array()),
            array('PasswordSecure$', array()),
            array('PasswordSecure!', array()),
            array('PasswordSecure_', array()),
        );
    }
}
