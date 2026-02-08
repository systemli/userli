<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\DomainCreateType;
use App\Form\Model\DomainCreate;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class DomainCreateTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        $domain = 'example.org';
        $formData = ['domain' => $domain];

        $model = new DomainCreate();
        $form = $this->factory->create(DomainCreateType::class, $model);

        $expected = new DomainCreate();
        $expected->setDomain($domain);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($expected, $model);
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(DomainCreateType::class);
        $view = $form->createView();

        self::assertArrayHasKey('domain', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(DomainCreateType::class);

        self::assertSame('create_domain', $form->getConfig()->getName());
    }
}
