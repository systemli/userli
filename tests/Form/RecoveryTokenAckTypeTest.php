<?php

namespace App\Tests\Form;

use App\Form\Model\RecoveryTokenAck;
use App\Form\RecoveryTokenAckType;
use Symfony\Component\Form\Test\TypeTestCase;

class RecoveryTokenAckTypeTest extends TypeTestCase
{
    public function testSubmitValidData()
    {
        $uuid = '550e8400-e29b-11d4-a716-446655440000';

        $formData = [
            'ack' => true,
            'recoveryToken' => $uuid,
        ];

        $form = $this->factory->create(RecoveryTokenAckType::class);

        $object = new RecoveryTokenAck();
        $object->ack = true;
        $object->recoveryToken = $uuid;

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
