<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\Domain;
use App\Form\DomainType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class DomainTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    public function testSubmitValidData(): void
    {
        $name = 'example.org';
        $formData = ['name' => $name];

        $domain = new Domain();
        $form = $this->factory->create(DomainType::class, $domain, [
            'validation_groups' => false,
        ]);

        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($name, $domain->getName());
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(DomainType::class);
        $view = $form->createView();

        self::assertArrayHasKey('name', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }
}
