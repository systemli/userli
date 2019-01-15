<?php

namespace App\Tests\Form;

use App\Form\Model\RecoveryResetPassword;
use App\Form\RecoveryResetPasswordType;
use Symfony\Component\Form\Test\TypeTestCase;

class RecoveryResetPasswordTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $email = 'user@example.org';
        $recoveryToken = 'recovery-token';
        $newPassword = 'password';
        $formData = [
            'email' => $email,
            'recoveryToken' => $recoveryToken,
            'newPassword' => [$newPassword, $newPassword],
            ];

        $form = $this->factory->create(RecoveryResetPasswordType::class);

        $object = new RecoveryResetPassword();
        $object->email = $email;
        $object->recoveryToken = $recoveryToken;
        $object->newPassword = [$newPassword, $newPassword];

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
