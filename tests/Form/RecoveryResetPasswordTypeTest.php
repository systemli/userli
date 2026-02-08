<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\RecoveryResetPassword;
use App\Form\RecoveryResetPasswordType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class RecoveryResetPasswordTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        $email = 'user@example.org';
        $recoveryToken = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        $password = 'new-secure-password';

        $formData = [
            'email' => $email,
            'recoveryToken' => $recoveryToken,
            'password' => [
                'first' => $password,
                'second' => $password,
            ],
        ];

        $model = new RecoveryResetPassword();
        $form = $this->factory->create(RecoveryResetPasswordType::class, $model);

        $expected = new RecoveryResetPassword();
        $expected->setEmail($email);
        $expected->setRecoveryToken($recoveryToken);
        $expected->setPassword($password);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $model);
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(RecoveryResetPasswordType::class);
        $view = $form->createView();

        self::assertArrayHasKey('email', $view->children);
        self::assertArrayHasKey('recoveryToken', $view->children);
        self::assertArrayHasKey('password', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }

    public function testEmailAndRecoveryTokenAreHidden(): void
    {
        $form = $this->factory->create(RecoveryResetPasswordType::class);
        $view = $form->createView();

        self::assertSame('hidden', $view->children['email']->vars['block_prefixes'][1]);
        self::assertSame('hidden', $view->children['recoveryToken']->vars['block_prefixes'][1]);
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(RecoveryResetPasswordType::class);

        self::assertSame('recovery_reset_password', $form->getConfig()->getName());
    }
}
