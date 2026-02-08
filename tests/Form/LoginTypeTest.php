<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\LoginType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class LoginTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            '_username' => 'user@example.org',
            '_password' => 'secure-password',
            '_remember_me' => true,
        ];

        $form = $this->factory->create(LoginType::class);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());

        $data = $form->getData();
        self::assertSame('user@example.org', $data['_username']);
        self::assertSame('secure-password', $data['_password']);
        self::assertTrue($data['_remember_me']);
    }

    public function testSubmitWithoutRememberMe(): void
    {
        $formData = [
            '_username' => 'user@example.org',
            '_password' => 'secure-password',
        ];

        $form = $this->factory->create(LoginType::class);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());

        $data = $form->getData();
        self::assertFalse($data['_remember_me']);
    }

    public function testLastUsernameOption(): void
    {
        $form = $this->factory->create(LoginType::class, null, [
            'last_username' => 'previous@example.org',
        ]);

        $view = $form->createView();
        self::assertSame('previous@example.org', $view->children['_username']->vars['value']);
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(LoginType::class);
        $view = $form->createView();

        self::assertArrayHasKey('_username', $view->children);
        self::assertArrayHasKey('_password', $view->children);
        self::assertArrayHasKey('_remember_me', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }

    public function testUsernameFieldAttributes(): void
    {
        $form = $this->factory->create(LoginType::class);
        $view = $form->createView();

        $usernameAttrs = $view->children['_username']->vars['attr'];
        self::assertSame('username email', $usernameAttrs['autocomplete']);
        self::assertTrue($usernameAttrs['autofocus']);
    }

    public function testPasswordFieldAttributes(): void
    {
        $form = $this->factory->create(LoginType::class);
        $view = $form->createView();

        $passwordAttrs = $view->children['_password']->vars['attr'];
        self::assertSame('current-password', $passwordAttrs['autocomplete']);
    }

    public function testFormHasNoBlockPrefix(): void
    {
        $form = $this->factory->create(LoginType::class);
        $view = $form->createView();

        // Empty block prefix means field names are not prefixed (e.g., _username instead of login[_username])
        self::assertSame('', $view->vars['name']);
    }

    public function testCsrfOptions(): void
    {
        $formType = new LoginType();
        $resolver = new \Symfony\Component\OptionsResolver\OptionsResolver();
        $formType->configureOptions($resolver);

        $options = $resolver->resolve([]);
        self::assertSame('_csrf_token', $options['csrf_field_name']);
        self::assertSame('authenticate', $options['csrf_token_id']);
    }
}
