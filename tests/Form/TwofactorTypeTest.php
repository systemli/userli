<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\Twofactor;
use App\Form\TwofactorType;
use Symfony\Component\Form\Test\TypeTestCase;

class TwofactorTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $password = 'secure-password';
        $formData = ['password' => $password];

        $model = new Twofactor();
        $form = $this->factory->create(TwofactorType::class, $model);

        $expected = new Twofactor();
        $expected->setPassword($password);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(TwofactorType::class);
        $view = $form->createView();

        $this->assertArrayHasKey('password', $view->children);
        $this->assertArrayHasKey('submit', $view->children);
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(TwofactorType::class);

        $this->assertSame('twofactor', $form->getConfig()->getName());
    }
}
