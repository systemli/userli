<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\LoginType;
use Symfony\Component\Form\Test\TypeTestCase;

class LoginTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            '_username' => 'user@example.org',
            '_password' => 'secure-password',
            '_remember_me' => true,
        ];

        $form = $this->factory->create(LoginType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();
        $this->assertSame('user@example.org', $data['_username']);
        $this->assertSame('secure-password', $data['_password']);
        $this->assertTrue($data['_remember_me']);
    }

    public function testSubmitWithoutRememberMe(): void
    {
        $formData = [
            '_username' => 'user@example.org',
            '_password' => 'secure-password',
        ];

        $form = $this->factory->create(LoginType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();
        $this->assertFalse($data['_remember_me']);
    }

    public function testLastUsernameOption(): void
    {
        $form = $this->factory->create(LoginType::class, null, [
            'last_username' => 'previous@example.org',
        ]);

        $view = $form->createView();
        $this->assertSame('previous@example.org', $view->children['_username']->vars['value']);
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(LoginType::class);
        $view = $form->createView();

        $this->assertArrayHasKey('_username', $view->children);
        $this->assertArrayHasKey('_password', $view->children);
        $this->assertArrayHasKey('_remember_me', $view->children);
        $this->assertArrayHasKey('submit', $view->children);
    }

    public function testUsernameFieldAttributes(): void
    {
        $form = $this->factory->create(LoginType::class);
        $view = $form->createView();

        $usernameAttrs = $view->children['_username']->vars['attr'];
        $this->assertSame('username email', $usernameAttrs['autocomplete']);
        $this->assertTrue($usernameAttrs['autofocus']);
    }

    public function testPasswordFieldAttributes(): void
    {
        $form = $this->factory->create(LoginType::class);
        $view = $form->createView();

        $passwordAttrs = $view->children['_password']->vars['attr'];
        $this->assertSame('current-password', $passwordAttrs['autocomplete']);
    }

    public function testFormHasNoBlockPrefix(): void
    {
        $form = $this->factory->create(LoginType::class);
        $view = $form->createView();

        // Empty block prefix means field names are not prefixed (e.g., _username instead of login[_username])
        $this->assertSame('', $view->vars['name']);
    }

    public function testCsrfOptions(): void
    {
        $formType = new LoginType();
        $resolver = new \Symfony\Component\OptionsResolver\OptionsResolver();
        $formType->configureOptions($resolver);

        $options = $resolver->resolve([]);
        $this->assertSame('_csrf_token', $options['csrf_field_name']);
        $this->assertSame('authenticate', $options['csrf_token_id']);
    }
}
