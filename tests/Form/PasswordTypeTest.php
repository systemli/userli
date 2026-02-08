<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\Password;
use App\Form\PasswordType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class PasswordTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

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

        self::assertTrue($form->isSynchronized());

        self::assertEquals($expected, $model);
    }
}
