<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Form\Model\RecoveryToken;
use App\Form\RecoveryTokenType;
use Symfony\Component\Form\Test\TypeTestCase;

class RecoveryTokenTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $password = 'password';
        $formData = ['password' => $password];

        $form = $this->factory->create(RecoveryTokenType::class);

        $object = new RecoveryToken();
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
