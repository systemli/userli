<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\DomainCreateType;
use App\Form\Model\DomainCreate;
use Symfony\Component\Form\Test\TypeTestCase;

class DomainCreateTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $domain = 'example.org';
        $formData = ['domain' => $domain];

        $model = new DomainCreate();
        $form = $this->factory->create(DomainCreateType::class, $model);

        $expected = new DomainCreate();
        $expected->setDomain($domain);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expected, $model);
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(DomainCreateType::class);
        $view = $form->createView();

        $this->assertArrayHasKey('domain', $view->children);
        $this->assertArrayHasKey('submit', $view->children);
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(DomainCreateType::class);

        $this->assertSame('create_domain', $form->getConfig()->getName());
    }
}
