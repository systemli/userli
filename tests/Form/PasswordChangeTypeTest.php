<?php

namespace App\Tests\Form;

use App\Form\Model\PasswordChange;
use App\Form\PasswordChangeType;
use Symfony\Component\Form\Test\TypeTestCase;

class PasswordChangeTypeTest extends TypeTestCase
{

    public function testSubmitValidData(): void
    {
        $password = 'password';
        $plainPassword = 'password';

        $formData = [
            'password' => $password,
            'plainPassword' => [
                'first' => $plainPassword,
                'second' => $plainPassword,
            ],
        ];

        $model = new PasswordChange();
        $form = $this->factory->create(PasswordChangeType::class, $model);

        $expected = new PasswordChange();
        $expected->setPassword($password);
        $expected->setPlainPassword($plainPassword);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($expected, $model);
    }
}
