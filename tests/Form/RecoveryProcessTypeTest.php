<?php

namespace App\Tests\Form;

use App\Form\Model\RecoveryProcess;
use App\Form\RecoveryProcessType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class RecoveryProcessTypeTest extends TypeTestCase
{
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [new RecoveryProcessType('example.org')],
                []
                )
        ];
    }

    public function testSubmitValidData()
    {
        $email = 'user@example.org';
        $recoveryToken = 'recovery-token';
        $formData = [
            'email' => $email,
            'recoveryToken' => $recoveryToken,
            ];

        $form = $this->factory->create(RecoveryProcessType::class);

        $object = new RecoveryProcess();
        $object->email = $email;
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
