<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\Password;
use App\Form\PasswordType;
use Symfony\Component\Form\Test\TypeTestCase;

class PasswordTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $password = 'password';
        $newPassword = 'password';

        $formData = [
            'password' => $password,
            'newPassword' => [
                'first' => $newPassword,
                'second' => $newPassword,
            ],
        ];

        $model = new Password();
        $form = $this->factory->create(PasswordType::class, $model);

        $expected = new Password();
        $expected->setPassword($password);
        $expected->setNewPassword($newPassword);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expected, $model);
    }
}
