<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\InitUserType;
use App\Form\Model\InitUser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class InitUserTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

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

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $model);
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(InitUserType::class);
        $view = $form->createView();

        self::assertArrayHasKey('password', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }

    public function testPasswordIsRepeatedType(): void
    {
        $form = $this->factory->create(InitUserType::class);
        $view = $form->createView();

        self::assertArrayHasKey('first', $view->children['password']->children);
        self::assertArrayHasKey('second', $view->children['password']->children);
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(InitUserType::class);

        self::assertSame('init_user', $form->getConfig()->getName());
    }
}
