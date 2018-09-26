<?php

namespace App\Tests\Form;

use Symfony\Component\Form\Test\TypeTestCase;
use App\Form\DeleteType;
use App\Form\Model\Delete;

class DeleteTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $password = 'password';
        $formData = array('password' => $password);

        $form = $this->factory->create(DeleteType::class);

        $object = new Delete();
        $object->password = $password;

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
