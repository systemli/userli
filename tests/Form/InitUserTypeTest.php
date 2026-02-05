<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\InitUserType;
use App\Form\Model\InitUser;
use Symfony\Component\Form\Test\TypeTestCase;

class InitUserTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $password = 'secure-password';
        $formData = [
            'password' => [
                'first' => $password,
                'second' => $password,
            ],
        ];

        $model = new InitUser();
        $form = $this->factory->create(InitUserType::class, $model);

        $expected = new InitUser();
        $expected->setPassword($password);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(InitUserType::class);
        $view = $form->createView();

        $this->assertArrayHasKey('password', $view->children);
        $this->assertArrayHasKey('submit', $view->children);
    }

    public function testPasswordIsRepeatedType(): void
    {
        $form = $this->factory->create(InitUserType::class);
        $view = $form->createView();

        $this->assertArrayHasKey('first', $view->children['password']->children);
        $this->assertArrayHasKey('second', $view->children['password']->children);
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(InitUserType::class);

        $this->assertSame('init_user', $form->getConfig()->getName());
    }
}
