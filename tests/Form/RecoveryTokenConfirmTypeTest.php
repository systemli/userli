<?php

namespace App\Tests\Form;

use App\Form\Model\RecoveryTokenConfirm;
use App\Form\RecoveryTokenConfirmType;
use Symfony\Component\Form\Test\TypeTestCase;

class RecoveryTokenConfirmTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $uuid = '550e8400-e29b-11d4-a716-446655440000';

        $formData = [
            'confirm' => true,
            'recoveryToken' => $uuid,
        ];

        $form = $this->factory->create(RecoveryTokenConfirmType::class);

        $object = new RecoveryTokenConfirm();
        $object->setConfirm(true);
        $object->setRecoveryToken($uuid);

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
