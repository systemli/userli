<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\ReservedNameImportType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class ReservedNameImportTypeTest extends TypeTestCase
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

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(ReservedNameImportType::class);
        $view = $form->createView();

        self::assertArrayHasKey('file', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }

    public function testFileFieldHasConstraints(): void
    {
        $form = $this->factory->create(ReservedNameImportType::class);
        $fileConfig = $form->get('file')->getConfig();
        $constraints = $fileConfig->getOption('constraints');

        self::assertNotEmpty($constraints);
        self::assertCount(2, $constraints);
    }
}
