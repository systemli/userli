<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\Twofactor;
use App\Form\TwofactorType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class TwofactorTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        $password = 'secure-password';
        $formData = ['password' => $password];

        $model = new Twofactor();
        $form = $this->factory->create(TwofactorType::class, $model);

        $expected = new Twofactor();
        $expected->setPassword($password);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $model);
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(TwofactorType::class);
        $view = $form->createView();

        self::assertArrayHasKey('password', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(TwofactorType::class);

        self::assertSame('twofactor', $form->getConfig()->getName());
    }
}
