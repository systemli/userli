<?php

declare(strict_types=1);

namespace App\Tests\Handler;

use App\Handler\PasswordStrengthHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PasswordStrengthHandlerTest extends TestCase
{
    #[DataProvider('dataProvider')]
    public function testValidate($input, $expected): void
    {
        $handler = new PasswordStrengthHandler();
        $actual = $handler->validate($input);

        self::assertEquals($expected, $actual);
    }

    public static function dataProvider(): array
    {
        return [
            ['password', ['form.weak_password']],
            ['Password', ['form.weak_password']],
            ['pässword', ['form.forbidden_char', 'form.weak_password']],
            ['PässwordSecure1', ['form.forbidden_char']],
            ['PasswördSecure1', ['form.forbidden_char']],
            ['PasswordSecüre1', ['form.forbidden_char']],
            ['PasswordSecure1\'', ['form.forbidden_char']],
            ['passwordpasswordpassword', []],
            ['PasswordSecure1', []],
            ['PasswordSecure$', []],
            ['PasswordSecure!', []],
            ['PasswordSecure_', []],
        ];
    }
}
