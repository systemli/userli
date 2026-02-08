<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\VoucherCreate;
use App\Form\VoucherCreateType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class VoucherCreateTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        $form = $this->factory->create(VoucherCreateType::class);
        $form->submit([]);

        self::assertTrue($form->isSynchronized());

        /** @var VoucherCreate $data */
        $data = $form->getData();
        self::assertInstanceOf(VoucherCreate::class, $data);
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(VoucherCreateType::class);
        $view = $form->createView();

        self::assertArrayHasKey('submit', $view->children);
    }

    public function testBlockPrefix(): void
    {
        $form = $this->factory->create(VoucherCreateType::class);

        self::assertSame('create_voucher', $form->getConfig()->getName());
    }
}
