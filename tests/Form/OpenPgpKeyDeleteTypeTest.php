<?php

namespace App\Tests\Form;

use App\Form\OpenPgpDeleteType;
use App\Form\Model\Delete;
use Symfony\Component\Form\Test\TypeTestCase;

class OpenPgpKeyDeleteTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $password = 'password';
        $formData = ['password' => $password];

        $form = $this->factory->create(OpenPgpDeleteType::class);

        $object = new Delete();
        $object->password = $password;

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
