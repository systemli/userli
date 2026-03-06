<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\PasswordConfirmation;
use App\Form\PasswordConfirmationType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class PasswordConfirmationTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        $password = 'password';
        $formData = ['password' => $password];

        $form = $this->factory->create(PasswordConfirmationType::class);

        $object = new PasswordConfirmation();
        $object->setPassword($password);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($object, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            self::assertArrayHasKey($key, $children);
        }
    }

    public function testCustomOptions(): void
    {
        $form = $this->factory->create(PasswordConfirmationType::class, null, [
            'password_label' => 'custom.password.label',
            'submit_label' => 'custom.submit.label',
        ]);

        $view = $form->createView();

        self::assertSame('custom.password.label', $view->children['password']->vars['label']);
        self::assertSame('custom.submit.label', $view->children['submit']->vars['label']);
    }

    public function testDefaultOptions(): void
    {
        $form = $this->factory->create(PasswordConfirmationType::class);

        $view = $form->createView();

        self::assertSame('form.delete-password', $view->children['password']->vars['label']);
        self::assertSame('form.submit', $view->children['submit']->vars['label']);
    }
}
