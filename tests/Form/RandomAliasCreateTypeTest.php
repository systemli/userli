<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\AliasCreate;
use App\Form\RandomAliasCreateType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class RandomAliasCreateTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        $formData = ['note' => 'My test alias'];

        $form = $this->factory->create(RandomAliasCreateType::class);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());

        /** @var AliasCreate $data */
        $data = $form->getData();
        self::assertSame('My test alias', $data->getNote());
    }

    public function testSubmitWithoutNote(): void
    {
        $formData = [];

        $form = $this->factory->create(RandomAliasCreateType::class);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());

        /** @var AliasCreate $data */
        $data = $form->getData();
        self::assertNull($data->getNote());
    }

    public function testNoteTrimmed(): void
    {
        $formData = ['note' => '  Trimmed Note  '];

        $form = $this->factory->create(RandomAliasCreateType::class);
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());

        /** @var AliasCreate $data */
        $data = $form->getData();
        self::assertSame('Trimmed Note', $data->getNote());
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(RandomAliasCreateType::class);
        $view = $form->createView();

        self::assertArrayHasKey('note', $view->children);
        self::assertArrayHasKey('submit', $view->children);
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(RandomAliasCreateType::class);

        self::assertSame('create_alias', $form->getConfig()->getName());
    }
}
