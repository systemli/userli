<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\VoucherCreate;
use App\Form\VoucherCreateType;
use Symfony\Component\Form\Test\TypeTestCase;

class VoucherCreateTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $form = $this->factory->create(VoucherCreateType::class);
        $form->submit([]);

        $this->assertTrue($form->isSynchronized());

        /** @var VoucherCreate $data */
        $data = $form->getData();
        $this->assertInstanceOf(VoucherCreate::class, $data);
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(VoucherCreateType::class);
        $view = $form->createView();

        $this->assertArrayHasKey('submit', $view->children);
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(VoucherCreateType::class);

        $this->assertSame('create_voucher', $form->getConfig()->getName());
    }
}
