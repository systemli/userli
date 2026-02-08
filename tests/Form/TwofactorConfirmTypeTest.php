<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\TwofactorConfirm;
use App\Form\TwofactorConfirmType;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Test\TypeTestCase;

class TwofactorConfirmTypeTest extends TypeTestCase
{
    protected function setUp(): void
    {
        $this->dispatcher = $this->createStub(EventDispatcherInterface::class);
        parent::setUp();
    }

    public function testSubmitValidData(): void
    {
        $totpSecret = 'secret';
        $formData = ['code' => $totpSecret];

        $form = $this->factory->create(TwofactorConfirmType::class);

        $object = new TwofactorConfirm();
        $object->setCode($totpSecret);

        // submit the data to the form directly
        $form->submit($formData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($object, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            self::assertArrayHasKey($key, $children);
        }
    }
}
