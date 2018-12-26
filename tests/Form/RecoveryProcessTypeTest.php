<?php

namespace App\Tests\Form;

use Symfony\Component\Form\Test\TypeTestCase;
use App\Form\RecoveryProcessType;
use App\Form\Model\RecoveryProcess;

class RecoveryProcessTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $username = 'user@example.org';
        $recoveryToken = 'recovery-token';
        $formData = [
            'username' => $username,
            'recoveryToken' => $recoveryToken,
            ];

        $form = $this->factory->create(RecoveryProcessType::class);

        $object = new RecoveryProcess();
        $object->username = $username;
        $object->recoveryToken = $recoveryToken;

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
