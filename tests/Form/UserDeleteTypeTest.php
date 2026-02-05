<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\Delete;
use App\Form\UserDeleteType;
use Symfony\Component\Form\Test\TypeTestCase;

class UserDeleteTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $password = 'password';
        $formData = ['password' => $password];

        $form = $this->factory->create(UserDeleteType::class);

        $object = new Delete();
        $object->setPassword($password);

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
