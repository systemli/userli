<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\AliasCreate;
use App\Form\RandomAliasCreateType;
use Symfony\Component\Form\Test\TypeTestCase;

class RandomAliasCreateTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = ['note' => 'My test alias'];

        $form = $this->factory->create(RandomAliasCreateType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        /** @var AliasCreate $data */
        $data = $form->getData();
        $this->assertSame('My test alias', $data->getNote());
    }

    public function testSubmitWithoutNote(): void
    {
        $formData = [];

        $form = $this->factory->create(RandomAliasCreateType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        /** @var AliasCreate $data */
        $data = $form->getData();
        $this->assertNull($data->getNote());
    }

    public function testNoteTrimmed(): void
    {
        $formData = ['note' => '  Trimmed Note  '];

        $form = $this->factory->create(RandomAliasCreateType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        /** @var AliasCreate $data */
        $data = $form->getData();
        $this->assertSame('Trimmed Note', $data->getNote());
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(RandomAliasCreateType::class);
        $view = $form->createView();

        $this->assertArrayHasKey('note', $view->children);
        $this->assertArrayHasKey('submit', $view->children);
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(RandomAliasCreateType::class);

        $this->assertSame('create_alias', $form->getConfig()->getName());
    }
}
