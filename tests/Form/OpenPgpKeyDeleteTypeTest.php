<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\Delete;
use App\Form\OpenPgpDeleteType;
use Symfony\Component\Form\Test\TypeTestCase;

class OpenPgpKeyDeleteTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $password = 'password';
        $formData = ['password' => $password];

        $form = $this->factory->create(OpenPgpDeleteType::class);

        $object = new Delete();
        $object->setPassword($password);

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
