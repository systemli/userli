<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\TwofactorConfirm;
use App\Form\TwofactorConfirmType;
use Symfony\Component\Form\Test\TypeTestCase;

class TwofactorConfirmTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $totpSecret = 'secret';
        $formData = ['code' => $totpSecret];

        $form = $this->factory->create(TwofactorConfirmType::class);

        $object = new TwofactorConfirm();
        $object->setCode($totpSecret);

        // submit the data to the form directly
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($object, $form->getData());

        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }
}
